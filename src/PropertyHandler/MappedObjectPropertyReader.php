<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\Field;
use Crell\Serde\TypeMapper;

// @todo I am not sure this is the right approach, because of the need for the
// analyzer in the parent.
class MappedObjectPropertyReader extends ObjectPropertyReader
{
    public function __construct(
        protected readonly array $supportedTypes,
        protected readonly TypeMapper $typeMap,
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    protected function typeMap(Field $field): ?TypeMapper
    {
        return $this->typeMap;
    }
}
