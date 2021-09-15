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
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($object, ClassDef::class);

        // @todo $format would get used here.
        $formatter = new JsonFormatter();

        $props = array_filter($objectMetadata->properties, $this->shouldSerialize(new \ReflectionObject($object), $object));

        $valueSerializer = fn (string $type, mixed $source, string $name, mixed $value): mixed
            => $this->serializeValue($formatter, $format, $type, $source, $name, $value);

        $propertySerializer = fn (mixed $runningValue, Field $field): mixed
            => $this->serializeProperty($valueSerializer, $object, $runningValue, $field);

        $serializedValue = array_reduce($props, $propertySerializer, $formatter->initialize());

        return $formatter->finalize($serializedValue);
    }

    protected function serializeProperty(callable $valueSerializer, object $object, mixed $runningValue, Field $field): mixed
    {
        $propName = $field->phpName;

        // @todo Figure out if we care about flattening/collecting objects.
        if ($field->flatten && $field->phpType === 'array') {
            foreach ($object->$propName as $k => $v) {
                $runningValue = $valueSerializer(\get_debug_type($v), $runningValue, $k, $v);
            }
            return $runningValue;
        }

        $name = $this->deriveSerializedName($field);
        return $valueSerializer($field->phpType, $runningValue, $name, $object->$propName);
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

    protected function serializeValue(JsonFormatter $formatter, string $format, string $phpType, mixed $runningValue, string $name, mixed $value): mixed
    {
        $extractor = $this->first($this->extractors, fn (Extractor $ex) => $ex->supportsExtract($phpType, $value, $format));

        if (!$extractor) {
            throw new \RuntimeException('No extractor for ' . $phpType);
        }

        return $extractor->extract($formatter, $format, $name, $value, $phpType, $runningValue);
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
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($to, ClassDef::class);

        $formatters['json'] = new JsonFormatter();
        $formatter = $formatters[$from];

        $valueDeserializer = fn(string $type, mixed $source, string $name): mixed
            => $this->deserializeValue($formatter, $from, $type, $source, $name);

        $decoded = $formatter->deserializeInitialize($serialized);

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
                 $props[$field->phpName] = $valueDeserializer($field->phpType, $decoded, $name);
             }
        }

        if ($collectingField) {
            $remaining = $formatter->getRemainingData($decoded, $usedNames);
            if ($collectingField->phpType === 'array') {
                foreach ($remaining as $k => $v) {
                    $props[$collectingField->phpName][$k] = $valueDeserializer(\get_debug_type($v), $remaining, $k);
                }
            }
            // @todo Do we support collecting into objects? Does that even make sense?
        }

        $rClass = new \ReflectionClass($to);
        $new = $rClass->newInstanceWithoutConstructor();

        // Get defaults from the constructor if necessary and possible.
        foreach ($rClass->getConstructor()?->getParameters() ?? [] as $param) {
            if ($props[$param->name] === SerdeError::Missing) {
                $props[$param->name] = $param->getDefaultValue();
            }
        }

        // @todo What should happen if something is still set to Missing?

        $populate = function (array $props) {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        };
        $populate->bindTo($new, $new)($props);
        return $new;
    }

    protected function deserializeValue(JsonFormatter $formatter, string $format, string $type, mixed $source, string $name): mixed
    {
        /** @var Injector $injector */
        $injector = $this->first($this->injectors, fn (Injector $in): bool => $in->supportsInject($type, $format));

        if (!$injector) {
            throw new \RuntimeException('No injector for ' . $type);
        }

        return $injector->getValue($formatter, $format, $source, $name, $type);
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
}
