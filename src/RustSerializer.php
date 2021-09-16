<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Extractor\DateTimeExtractor;
use Crell\Serde\Extractor\DictionaryExtractor;
use Crell\Serde\Extractor\Extractor;
use Crell\Serde\Extractor\Injector;
use Crell\Serde\Extractor\ObjectExtractor;
use Crell\Serde\Extractor\SerializerAware;
use Crell\Serde\Extractor\ScalarExtractor;
use Crell\Serde\Extractor\SequenceExtractor;

class RustSerializer
{
    protected ClassAnalyzer $analyzer;

    /** @var Extractor[]  */
    protected array $extractors = [];

    /** @var Injector[] */
    protected array $injectors = [];

    public function __construct()
    {
        $this->analyzer = new Analyzer();

        $this->addExtractor(new ScalarExtractor());
        $this->addExtractor(new SequenceExtractor());
        $this->addExtractor(new DictionaryExtractor());
        $this->addExtractor(new DateTimeExtractor());
        $this->addExtractor(new ObjectExtractor());
    }

    public function addExtractor(Extractor|Injector $v): static
    {
        if ($v instanceof SerializerAware) {
            $v->setSerializer($this);
        }

        if ($v instanceof Extractor) {
            $this->extractors[] = $v;
        }
        if ($v instanceof Injector) {
            $this->injectors[] = $v;
        }

        return $this;
    }

    public function serialize(object $object, string $format): string
    {
        // @todo $format would get used here.
        $formatter = new JsonFormatter();

        $init = $formatter->initialize();

        $serializedValue = $this->innerSerialize($formatter, $format, $object, $init);

        return $formatter->finalize($serializedValue);
    }

    // @todo This is ugly and gross.
    public function innerSerialize(JsonFormatter $formatter, string $format, object $object, mixed $runningValue): mixed
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($object, ClassDef::class);

        $props = array_filter($objectMetadata->properties, $this->shouldSerialize(new \ReflectionObject($object), $object));

        $valueSerializer = fn (Field $field, mixed $source, string $name, mixed $value): mixed
        => $this->serializeValue($formatter, $format, $field, $source, $name, $value);

        $propertySerializer = fn (mixed $runningValue, Field $field): mixed
        => $this->serializeProperty($valueSerializer, $object, $runningValue, $field);

        return array_reduce($props, $propertySerializer, $runningValue);
    }

    protected function serializeProperty(callable $valueSerializer, object $object, mixed $runningValue, Field $field): mixed
    {
        $propName = $field->phpName;

        // @todo Figure out if we care about flattening/collecting objects.
        if ($field->flatten && $field->phpType === 'array') {
            foreach ($object->$propName as $k => $v) {
                $runningValue = $valueSerializer($this->makePseudoFieldForValue($k, $v), $runningValue, $k, $v);
            }
            return $runningValue;
        }

        $name = $this->deriveSerializedName($field);
        return $valueSerializer($field, $runningValue, $name, $object->$propName);
    }

    protected function serializeValue(JsonFormatter $formatter, string $format, Field $field, mixed $runningValue, string $name, mixed $value): mixed
    {
        /** @var Extractor $extractor */
        $extractor = $this->first($this->extractors, fn (Extractor $ex) => $ex->supportsExtract($field, $value,
            $format));

        if (!$extractor) {
            throw new \RuntimeException('No extractor for ' . $field->phpType);
        }

        return $extractor->extract($formatter, $format, $name, $value, $field, $runningValue);
    }

    protected function shouldSerialize(\ReflectionObject $rObject, object $object): callable
    {
        // @todo Do we serialize nulls or no? Right now we don't.
        return static fn (Field $field) =>
            $rObject->getProperty($field->phpName)->isInitialized($object)
            && !is_null($object->{$field->phpName});
    }

    public function deserialize(string $serialized, string $from, string $to): object
    {
        $formatters['json'] = new JsonFormatter();
        $formatter = $formatters[$from];

        $decoded = $formatter->deserializeInitialize($serialized);

        $new = $this->innerDeserialize($formatter, $from, $decoded, $to);

        $formatter->finalizeDeserialize($decoded);

        return $new;
    }

    public function innerDeserialize(JsonFormatter $formatter, string $format, mixed $decoded, string $targetType): mixed
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($targetType, ClassDef::class);

        $valueDeserializer = fn(Field $field, mixed $source, string $name): mixed
        => $this->deserializeValue($formatter, $format, $field, $source, $name);

        $props = [];
        $usedNames = [];
        $collectingField = null;

        // Build up an array of properties that we can then assign all at once.
        foreach ($objectMetadata->properties as $field) {
            $name = $this->deriveSerializedName($field);

            $usedNames[] = $name;
            if ($field->flatten) {
                $collectingField = $field;
            } else {
                $props[$field->phpName] = $valueDeserializer($field, $decoded, $name);
            }
        }

        if ($collectingField) {
            $remaining = $formatter->getRemainingData($decoded, $usedNames);
            if ($collectingField->phpType === 'array') {
                foreach ($remaining as $k => $v) {
                    $props[$collectingField->phpName][$k] = $valueDeserializer($this->makePseudoFieldForValue($k, $v), $remaining, $k);
                }
            }
            // @todo Do we support collecting into objects? Does that even make sense?
        }

        // @todo What should happen if something is still set to Missing?
        $rClass = new \ReflectionClass($targetType);
        $new = $rClass->newInstanceWithoutConstructor();

        // Get defaults from the constructor if necessary and possible.
        foreach ($rClass->getConstructor()?->getParameters() ?? [] as $param) {
            if ($props[$param->name] === SerdeError::Missing) {
                $props[$param->name] = $param->getDefaultValue();
            }
        }

        $populate = function (array $props) {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        };
        $populate->bindTo($new, $new)($props);
        return $new;
    }

    protected function deserializeValue(JsonFormatter $formatter, string $format, Field $field, mixed $source, string $name): mixed
    {
        /** @var Injector $injector */
        $injector = $this->first($this->injectors, fn (Injector $in): bool => $in->supportsInject($field, $format));

        if (!$injector) {
            throw new \RuntimeException('No injector for ' . $field->phpType);
        }

        return $injector->getValue($formatter, $format, $source, $name, $field->phpType);
    }

    protected function deriveSerializedName(Field $field): string
    {
        $name = $field->phpName;

        if ($field->name) {
            $name = $field->name;
        }

        if ($field->caseFold !== Cases::Unchanged) {
            $name = $field->caseFold->convert($name);
        }

        return $name;
    }

    // @todo Redesign this so we can make phpType readonly.
    protected function makePseudoFieldForValue(string $name, mixed $value): Field
    {
        $f = new Field(name: $name);
        $f->phpType = \get_debug_type($value);
        return $f;
    }

    // @todo Needs to be a first() function from FP.
    private function first(iterable $list, callable $c): mixed
    {
        foreach ($list as $k => $v) {
            if ($c($v, $k)) {
                return $v;
            }
        }
        return null;
    }
}
