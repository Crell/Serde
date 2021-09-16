<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\PropertyHandler\DateTimePropertyReader;
use Crell\Serde\PropertyHandler\DictionaryPropertyReader;
use Crell\Serde\PropertyHandler\PropertyReader;
use Crell\Serde\PropertyHandler\PropertyWriter;
use Crell\Serde\PropertyHandler\ObjectPropertyReader;
use Crell\Serde\PropertyHandler\ScalarPropertyReader;
use Crell\Serde\PropertyHandler\SequencePropertyReader;

class RustSerializer
{
    protected ClassAnalyzer $analyzer;

    /** @var PropertyReader[]  */
    protected array $readers = [];

    /** @var PropertyWriter[] */
    protected array $writers = [];

    public function __construct()
    {
        $this->analyzer = new Analyzer();

        $this->addPropertyHandler(new ScalarPropertyReader());
        $this->addPropertyHandler(new SequencePropertyReader());
        $this->addPropertyHandler(new DictionaryPropertyReader());
        $this->addPropertyHandler(new DateTimePropertyReader());
        $this->addPropertyHandler(new ObjectPropertyReader());
    }

    public function addPropertyHandler(PropertyReader|PropertyWriter $v): static
    {
        if ($v instanceof PropertyReader) {
            $this->readers[] = $v;
        }
        if ($v instanceof PropertyWriter) {
            $this->writers[] = $v;
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

    protected function innerSerialize(JsonFormatter $formatter, string $format, object $object, mixed $runningValue): mixed
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($object, ClassDef::class);

        $props = array_filter($objectMetadata->properties, $this->shouldSerialize(new \ReflectionObject($object), $object));

        $valueSerializer = fn (Field $field, mixed $runningVal, mixed $value): mixed
        => $this->serializeValue($formatter, $format, $field, $runningVal, $value);

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
                $f = Field::create(name: $k, phpName: $k, phpType: \get_debug_type($v));
                $runningValue = $valueSerializer($f, $runningValue, $v);
            }
            return $runningValue;
        }

        return $valueSerializer($field, $runningValue, $object->$propName);
    }

    protected function serializeValue(JsonFormatter $formatter, string $format, Field $field, mixed $runningValue, mixed $value): mixed
    {
        /** @var PropertyReader $reader */
        $reader = $this->first($this->readers, fn (PropertyReader $ex) => $ex->canRead($field, $value, $format));

        if (!$reader) {
            // @todo Better exception.
            throw new \RuntimeException('No reader for ' . $field->phpType);
        }

        $recursor = fn (mixed $value, mixed $runValue) => $this->innerSerialize($formatter, $format, $value, $runValue);
        return $reader->readValue($formatter, $recursor, $field, $value, $runningValue);
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

    protected function innerDeserialize(JsonFormatter $formatter, string $format, mixed $decoded, string $targetType): mixed
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($targetType, ClassDef::class);

        $valueDeserializer = fn(Field $field, mixed $source): mixed
            => $this->deserializeValue($formatter, $format, $field, $source);

        $props = [];
        $usedNames = [];
        $collectingField = null;

        // Build up an array of properties that we can then assign all at once.
        foreach ($objectMetadata->properties as $field) {
            $usedNames[] = $field->serializedName();
            if ($field->flatten) {
                $collectingField = $field;
            } else {
                $props[$field->phpName] = $valueDeserializer($field, $decoded);
            }
        }

        if ($collectingField) {
            $remaining = $formatter->getRemainingData($decoded, $usedNames);
            if ($collectingField->phpType === 'array') {
                foreach ($remaining as $k => $v) {
                    $f = Field::create(name: $k, phpName: $k, phpType: \get_debug_type($v));
                    $props[$collectingField->phpName][$k] = $valueDeserializer($f, $remaining, $k);
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

    protected function deserializeValue(JsonFormatter $formatter, string $format, Field $field, mixed $source): mixed
    {
        /** @var PropertyWriter $writer */
        $writer = $this->first($this->writers, fn (PropertyWriter $in): bool => $in->canWrite($field, $format));

        if (!$writer) {
            // @todo Better exception.
            throw new \RuntimeException('No writer for ' . $field->phpType);
        }

        $recursor = fn (mixed $value, $target) => $this->innerDeserialize($formatter, $format, $value, $target);
        return $writer->writeValue($formatter, $recursor, $source, $field);
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
