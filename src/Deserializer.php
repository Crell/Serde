<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\SupportsCollecting;
use Crell\Serde\PropertyHandler\PropertyReader;
use Crell\Serde\PropertyHandler\PropertyWriter;
use function Crell\fp\first;
use function Crell\fp\pipe;

// This exists mainly just to create a closure over the format and formatter.
// But that does simplify a number of functions.
class Deserializer
{
    /**
     * Reference to the deserialize() method.
     *
     * This recursor gets passed through to the formatter, and may
     * get called recursively.  Storing a single reference rather than
     * making a new one each time is a minor performance optimization.
     */
    protected readonly \Closure $recursor;

    public function __construct(
        protected readonly ClassAnalyzer $analyzer,
        /** @var PropertyReader[]  */
        protected readonly array $readers,
        /** @var PropertyWriter[] */
        protected readonly array $writers,
        protected readonly Deformatter $formatter,
    ) {
        $this->recursor = $this->deserialize(...);
    }

    public function deserialize(mixed $decoded, Field $field): mixed
    {
        $writer = $this->findWriter($field);
        $result = $writer->writeValue($this->formatter, $this->recursor, $field, $decoded);

        return $result;
    }

    protected function findWriter(Field $field): PropertyWriter
    {
        foreach ($this->writers as $w) {
            if ($w->canWrite($field, $this->formatter->format())) {
                return $w;
            }
        }

        // @todo Better exception.
        throw new \RuntimeException('No writer for ' . $field->phpType);
    }
}
