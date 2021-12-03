<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\Deserializer;
use Crell\Serde\Field;
use Crell\Serde\Serializer;
use Crell\Serde\TypeMap;
use function Crell\fp\firstValue;

// @todo I am not sure this is the right approach, because of the need for the
// analyzer in the parent.
class MappedObjectPropertyReader extends ObjectPropertyReader
{
    public function __construct(
        protected readonly array $supportedTypes,
        protected readonly TypeMap $typeMap,
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    protected function typeMap(Serializer|Deserializer $serializer, Field $field): ?TypeMap
    {
        return $this->typeMap;
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return (bool)firstValue(fn (string $candidate): bool => $this->classImplements($field->phpType, $candidate))($this->supportedTypes);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return (bool)firstValue(fn (string $candidate): bool => $this->classImplements($field->phpType, $candidate))($this->supportedTypes);
    }

    /**
     * Determines if a class name extends or implements a given class/interface.
     *
     * @param string $class
     *   The class name to check.
     * @param string $interface
     *   The class or interface to look for.
     * @return bool
     */
    protected function classImplements(string $class, string $interface): bool
    {
        // class_parents() and class_implements() return a parallel k/v array. The key lookup is faster.
        return $class === $interface || isset(class_parents($class)[$interface]) || isset(class_implements($class)[$interface]);
    }
}
