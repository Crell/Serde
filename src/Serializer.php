<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\PropertyHandler\Exporter;

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

    /**
     * @param Exporter[] $exporters
     * @param array<string|null> $scopes
     */
    public function __construct(
        public readonly ClassAnalyzer $analyzer,
        protected readonly array $exporters,
        public readonly Formatter $formatter,
        public readonly TypeMapper $typeMapper,
        public readonly array $scopes = [],
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

        $result = $this->doSerialize($value, $runningValue, $field);

        if (is_object($value)) {
            array_pop($this->seenObjects);
        }

        return $result;
    }

    /**
     * @internal
     *
     * There's one case where we need the circular detection disabled, and that's when
     * dealing with mixed fields.  A mixed field throws its value back through the serializer,
     * which is normally fine but would trigger the circular reference checks in serialize().
     * Instead, we split it out to a separate method that no one should use except MixedExporter.
     * That means if you're reading this, you should walk away now because you're not supposed
     * to use this method.
     */
    public function doSerialize(mixed $value, mixed $runningValue, Field $field): mixed
    {
        return $this->findExporter($field, $value)->exportValue($this, $field, $value, $runningValue);
    }

    protected function findExporter(Field $field, mixed $value): Exporter
    {
        $format = $this->formatter->format();
        foreach ($this->exporters as $r) {
            if ($r->canExport($field, $value, $format)) {
                return $r;
            }
        }

        throw NoExporterFound::create($field->phpType, $format);
    }

    /**
     * Look up properties for the specified class.
     *
     * This is context-aware, so will include filtering for the current
     * scope, for instance.
     *
     * @param class-string $class
     * @return Field[]
     */
    public function propertiesFor(string $class): array
    {
        return $this->analyzer->analyze($class, ClassSettings::class, $this->scopes)->properties;
    }
}
