<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\PropertyHandler\Importer;

// This exists mainly just to create a closure over the format and formatter.
// But that does simplify a number of functions.
class Deserializer
{
    /**
     * @param Importer[] $importers
     * @param array<string|null> $scopes
     */
    public function __construct(
        public readonly ClassAnalyzer $analyzer,
        protected readonly array $importers,
        public readonly Deformatter $deformatter,
        public readonly TypeMapper $typeMapper,
        public readonly array $scopes = [],
    ) {}

    public function deserialize(mixed $decoded, Field $field): mixed
    {
        $writer = $this->findImporter($field);
        $result = $writer->importValue($this, $field, $decoded);

        return $result;
    }

    protected function findImporter(Field $field): Importer
    {
        $format = $this->deformatter->format();
        foreach ($this->importers as $w) {
            if ($w->canImport($field, $format)) {
                return $w;
            }
        }

        throw NoImporterFound::create($field->phpType, $format);
    }
}
