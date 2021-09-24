<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\PropertyHandler\PropertyReader;
use Crell\Serde\PropertyHandler\PropertyWriter;
use function Crell\fp\first;
use function Crell\fp\pipe;

// This exists mainly just to create a closure over the format and formatter.
// But that does simplify a number of functions.
class Serializer
{
    public function __construct(
        protected readonly ClassAnalyzer $analyzer,
        /** @var PropertyReader[]  */
        protected readonly array $readers,
        /** @var PropertyWriter[] */
        protected readonly array $writers,
        protected readonly JsonFormatter $formatter,
        protected readonly string $format,
    ) {}

    public function serialize(object $object, mixed $runningValue): mixed
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($object, ClassDef::class);

        $props = array_filter($objectMetadata->properties, $this->shouldSerialize(new \ReflectionObject($object), $object));

        $propertySerializer = fn (mixed $runningValue, Field $field): mixed
        => $this->serializeProperty($object, $runningValue, $field);

        return array_reduce($props, $propertySerializer, $runningValue);
    }

    protected function serializeProperty(object $object, mixed $runningValue, Field $field): mixed
    {
        $propName = $field->phpName;

        // This lets us read private values without messing with the Reflection API.
        $propReader = (fn (string $prop) => $this->$prop)->bindTo($object);

        // @todo Figure out if we care about flattening/collecting objects.
        if ($field->flatten && $field->phpType === 'array') {
            foreach ($propReader($propName) as $k => $v) {
                $f = Field::create(name: $k, phpName: $k, phpType: \get_debug_type($v));
                $runningValue = $this->serializeValue($f, $runningValue, $v);
            }
            return $runningValue;
        }

        return $this->serializeValue($field, $runningValue, $propReader($propName));
    }

    protected function serializeValue(Field $field, mixed $runningValue, mixed $value): mixed
    {
        /** @var PropertyReader $reader */
        $reader = pipe($this->readers, first(fn (PropertyReader $r) => $r->canRead($field, $value, $this->format)))
            ?? throw new \RuntimeException('No reader for ' . $field->phpType);

        return $reader->readValue($this->formatter, $this->serialize(...), $field, $value, $runningValue);
    }

    protected function shouldSerialize(\ReflectionObject $rObject, object $object): callable
    {
        // @todo Do we serialize nulls or no? Right now we don't.
        return static fn (Field $field) =>
            $rObject->getProperty($field->phpName)->isInitialized($object)
            && !is_null($object->{$field->phpName});
    }
}
