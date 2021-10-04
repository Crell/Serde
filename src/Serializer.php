<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\PropertyHandler\PropertyReader;
use Crell\Serde\PropertyHandler\PropertyWriter;
use function Crell\fp\first;
use function Crell\fp\pipe;

// This exists mainly just to create a closure over the formatter.
// But that does simplify a number of functions.
class Serializer
{
    /**
     * Used for circular reference loop detection.
     */
    protected array $seenObjects = [];

    protected \Closure $recursor;

    public function __construct(
        protected readonly ClassAnalyzer $analyzer,
        /** @var PropertyReader[]  */
        protected readonly array $readers,
        /** @var PropertyWriter[] */
        protected readonly array $writers,
        protected readonly Formatter $formatter,
    ) {
        $this->recursor = $this->serialize(...);
    }

    public function serialize(object $object, mixed $runningValue): mixed
    {
        // Had we partial application, we could easily factor the loop detection
        // out to its own method. Sadly it's needlessly convoluted to do otherwise.
        if (in_array($object, $this->seenObjects, true)) {
            throw CircularReferenceDetected::create($object);
        }
        $this->seenObjects[] = $object;

        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($object, ClassDef::class);

        $props = array_filter($objectMetadata->properties, $this->fieldHasValue($object));

        $propertySerializer = fn (mixed $runningValue, Field $field): mixed
        => $this->serializeProperty($object, $runningValue, $field);

        $result = array_reduce($props, $propertySerializer, $runningValue);

        array_pop($this->seenObjects);
        return $result;
    }

    protected function serializeProperty(object $object, mixed $runningValue, Field $field): mixed
    {
        $propName = $field->phpName;

        // This lets us read private values without messing with the Reflection API.
        $propReader = (fn (string $prop) => $this->$prop)->bindTo($object, $object);

        // @todo Figure out if we care about flattening/collecting objects.
        if ($field->flatten && $field->phpType === 'array') {
            foreach ($propReader($propName) as $k => $v) {
                $f = Field::create(serializedName: $k, phpName: $k, phpType: \get_debug_type($v));
                $runningValue = $this->serializeValue($f, $runningValue, $v);
            }
            return $runningValue;
        }

        return $this->serializeValue($field, $runningValue, $propReader($propName));
    }

    protected function serializeValue(Field $field, mixed $runningValue, mixed $value): mixed
    {
        /** @var PropertyReader $reader */
        $reader = pipe($this->readers, first(fn (PropertyReader $r) => $r->canRead($field, $value, $this->formatter->format())))
            ?? throw new \RuntimeException('No reader for ' . $field->phpType);

        return $reader->readValue($this->formatter, $this->recursor, $field, $value, $runningValue);
    }

    protected function fieldHasValue(object $object): callable
    {
        // This lets us read private values without messing with the Reflection API.
        $propReader = (fn (string $prop) => $this->$prop ?? null)->bindTo($object, $object);

        // @todo Do we serialize nulls or no? Right now we don't.
        return static fn (Field $field) =>
            !is_null($propReader($field->phpName));
    }
}
