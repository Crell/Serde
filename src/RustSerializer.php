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

        $eachProp = function ($runningValue, Field $field) use ($formatter, $object, $format) {
            $name = $this->mangle($field->name);
            return match ($field->phpType) {
                'int' => $formatter->serializeInt($runningValue, $name, $object->$name),
                'float' => $formatter->serializeFloat($runningValue, $name, $object->$name),
                'bool' => $formatter->serializeBool($runningValue, $name, $object->$name),
                'string' => $formatter->serializeString($runningValue, $name, $object->$name),
                'array' => $formatter->serializeArray($runningValue, $name, $object->$name),
                'resource' => throw ResourcePropertiesNotAllowed::create($field->name),
                \DateTime::class => $formatter->serializeDateTime($runningValue, $name, $object->$name),
                \DateTimeImmutable::class => $formatter->serializeDateTimeImmutable($runningValue, $name, $object->$name),
                // We assume anything else means an object.
                default => $formatter->serializeObject($runningValue, $name, $object->$name, $this, $format),
            };
        };

        $runningValue = array_reduce($props, $eachProp, $formatter->initialize());

        return $formatter->finalize($runningValue);
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

        $props = [];

        $decoded = $formatter->deserializeInitialize($serialized);

        // Build up an array of properties that we can then assign all at once.
        foreach ($objectMetadata->properties as $field) {
            $name = $this->mangle($field->name);
            $props[$field->name] = match ($field->phpType) {
                'int' => $formatter->deserializeInt($decoded, $name),
                'float' => $formatter->deserializeFloat($decoded, $name),
                'bool' => $formatter->deserializeBool($decoded, $name),
                'string' => $formatter->deserializeString($decoded, $name),
                'array' => $formatter->deserializeArray($decoded, $name),
                'resource' => throw ResourcePropertiesNotAllowed::create($field->name),
                \DateTime::class => $formatter->deserializeDateTime($decoded, $name),
                \DateTimeImmutable::class => $formatter->deserializeDateTimeImmutable($decoded, $name),
                // We assume anything else means an object.
                default => $formatter->deserializeObject($decoded, $name, $this, $from, $field->phpType),
            };
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

    protected function mangle(string $name): string
    {
        return $name;
    }
}
