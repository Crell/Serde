<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;

class RustSerializer
{
    protected ClassAnalyzer $analyzer;

    public function __construct()
    {
        $this->analyzer = new Analyzer();
    }

    public function serialize(object $object, string $format): string
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($object, ClassDef::class);

        // @todo $format would get used here.
        $formatter = new JsonFormatter();

        $props = array_filter($objectMetadata->properties, $this->shouldSerialize(new \ReflectionObject($object), $object));

        $valueSerializer = $this->makeValueSerializer($formatter, $format);
        $propertySerializer = $this->makePropertySerializer($valueSerializer, $object);

        $serializedValue = array_reduce($props, $propertySerializer, $formatter->initialize());

        return $formatter->finalize($serializedValue);
    }

    protected function makePropertySerializer(callable $valueSerializer, object $object): callable
    {
        return function ($runningValue, Field $field) use ($object, $valueSerializer) {
            $propName = $field->phpName;

            // @todo Figure out if we care about flattening/collecting objects.
            if ($field->flatten && $field->phpType === 'array') {
                foreach ($object->$propName as $k => $v) {
                    // @todo We really should standardize between the various ways of labeling types.
                    $runningValue = $valueSerializer(\get_debug_type($v), $runningValue, $k, $v);
                }
                return $runningValue;
            }

            $name = $this->deriveSerializedName($field);
            return $valueSerializer($field->phpType, $runningValue, $name, $object->$propName);
        };
    }

    protected function makeValueSerializer(JsonFormatter $formatter, string $format): callable
    {
        return fn (string $phpType, mixed $runningValue, string $name, mixed $value) => match ($phpType) {
            'int' => $formatter->serializeInt($runningValue, $name, $value),
            'float' => $formatter->serializeFloat($runningValue, $name, $value),
            'bool' => $formatter->serializeBool($runningValue, $name, $value),
            'string' => $formatter->serializeString($runningValue, $name, $value),
            'array' => $formatter->serializeArray($runningValue, $name, $value),
            'resource' => throw ResourcePropertiesNotAllowed::create($name),
            \DateTime::class => $formatter->serializeDateTime($runningValue, $name, $value),
            \DateTimeImmutable::class => $formatter->serializeDateTimeImmutable($runningValue, $name, $value),
            // We assume anything else means an object.
            default => $formatter->serializeObject($runningValue, $name, $value, $this, $format),
        };
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
        return match ($type) {
            'int' => $formatter->deserializeInt($source, $name),
            'float' => $formatter->deserializeFloat($source, $name),
            'bool' => $formatter->deserializeBool($source, $name),
            'string' => $formatter->deserializeString($source, $name),
            'array' => $formatter->deserializeArray($source, $name),
            'resource' => throw ResourcePropertiesNotAllowed::create($name),
            \DateTime::class => $formatter->deserializeDateTime($source, $name),
            \DateTimeImmutable::class => $formatter->deserializeDateTimeImmutable($source, $name),
            // We assume anything else means an object.
            default => $formatter->deserializeObject($source, $name, $this, $format, $type),
        };
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
