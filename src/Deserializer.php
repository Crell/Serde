<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\PropertyHandler\PropertyReader;
use Crell\Serde\PropertyHandler\PropertyWriter;

// This exists mainly just to create a closure over the format and formatter.
// But that does simplify a number of functions.
class Deserializer
{
    public function __construct(
        protected readonly ClassAnalyzer $analyzer,
        /** @var PropertyWriter[] */
        protected readonly array $writers,
        public readonly Deformatter $deformatter,
    ) {}

    public function deserialize(mixed $decoded, Field $field): mixed
    {
        $writer = $this->findWriter($field);
        $result = $writer->writeValue($this, $field, $decoded);

        return $result;
    }

    protected function findWriter(Field $field): PropertyWriter
    {
        $format = $this->deformatter->format();
        foreach ($this->writers as $w) {
            if ($w->canWrite($field, $format)) {
                return $w;
            }
        }

        throw NoWriterFound::create($field->phpType, $format);
    }
}
