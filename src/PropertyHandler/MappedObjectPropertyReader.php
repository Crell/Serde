<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;
use Crell\Serde\Records\Tasks\Task;
use Crell\Serde\TypeMapper;

class MappedObjectPropertyReader extends ObjectPropertyReader
{
    /**
     * @var array<class-string, ?TypeMapper>
     */
    private array $typeMapCache = [];

    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    public function readValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        return $formatter->serializeObject($runningValue, $field, $value, $recursor, [$this->keyName($field) => $this->classToIdentifier($value::class, $field)]);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return is_object($value)
            && $this->findMap($field);
            //&& any(static fn (string $class) => $value instanceof $class)($this->supportedTypes());
    }

    public function writeValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        $phpType = $this->identifierToClass($source[$field->serializedName()][$this->keyName($field)], $field);
        return parent::writeValue($formatter, $recursor, $field->with(phpType: $phpType), $source);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return (class_exists($field->phpType) || interface_exists($field->phpType)) && $this->findMap($field);
//        return any(static fn (string $class) => $field->phpType === $class || is_subclass_of($field->phpType, $class))($this->supportedTypes());
    }

    protected function supportedTypes(Field $field): array
    {
        return [Task::class];
    }

    protected function keyName(Field $field): string
    {
        return $this->findMap($field)->key;
    }

    protected function identifierToClass(string $identifier, Field $field): string
    {
        return $this->findMap($field)->findClass($identifier);
    }

    protected function classToIdentifier(string $class, Field $field): string
    {
        return $this->findMap($field)->findIdentifier($class);
    }

    protected function findMap(Field $field): ?TypeMapper
    {
        return $this->typeMapCache[$field->phpType] ??= $this->deriveMap($field);
    }

    protected function deriveMap(Field $field): ?TypeMapper
    {
        // @todo This logic may make more sense in ClassAnalyzer somewhere.
        // That would make it easier to test, too.

        // A map on the field itself takes priority.
        if ($field->typeMap) {
            return $field->typeMap;
        }

        // Scan up the inheritance hierarchy looking for maps.
        $classes = [$field->phpType, ...class_parents($field->phpType), ...class_implements($field->phpType)];
        foreach ($classes as $class) {
            if ($map = $this->analyzer->analyze($class, ClassDef::class)->typeMap) {
                return $map;
            }
        }

        return null;
    }
}
