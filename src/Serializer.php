<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\PropertyHandler\PropertyReader;

// This exists mainly just to create a closure over the formatter.
// But that does simplify a number of functions.
class Serializer
{
    /**
     * Used for circular reference loop detection.
     *
     * @var object[]
     */
    protected array $seenObjects = [];

    public function __construct(
        public readonly ClassAnalyzer $analyzer,
        /** @var PropertyReader[]  */
        protected readonly array $readers,
        public readonly Formatter $formatter,
        public readonly TypeMapper $typeMapper,
    ) {}

    public function serialize(mixed $value, mixed $runningValue, Field $field): mixed
    {
        // Had we partial application, we could easily factor the loop detection
        // out to its own method. Sadly it's needlessly convoluted to do otherwise.
        if (is_object($value)) {
            if (in_array($value, $this->seenObjects, true)) {
                throw CircularReferenceDetected::create($value);
            }
            $this->seenObjects[] = $value;
        }

        $reader = $this->findReader($field, $value);
        $result = $reader->readValue($this, $field, $value, $runningValue);

        if (is_object($value)) {
            array_pop($this->seenObjects);
        }

        return $result;
    }

    protected function findReader(Field $field, mixed $value): PropertyReader
    {
        $format = $this->formatter->format();
        foreach ($this->readers as $r) {
            if ($r->canRead($field, $value, $format)) {
                return $r;
            }
        }

        throw NoReaderFound::create($field->phpType, $format);
    }
}
