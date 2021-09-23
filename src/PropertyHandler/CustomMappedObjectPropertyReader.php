<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;
use Crell\Serde\TypeMapper;
use function Crell\fp\any;

class CustomMappedObjectPropertyReader extends ObjectPropertyReader
{
    public function __construct(
        protected readonly array $supportedTypes,
        protected readonly TypeMapper $typeMap,
    ) {}

    public function readValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $identifier = $this->typeMap->findIdentifier($value::class);
        return $formatter->serializeObject($runningValue, $field, $value, $recursor, [$this->typeMap->keyField() => $identifier]);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return is_object($value)
            && any(static fn (string $class) => $value instanceof $class)($this->supportedTypes);
    }

    public function writeValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        $identifier = $source[$field->serializedName()][$this->typeMap->keyField()];
        $phpType = $this->typeMap->findClass($identifier);
        return parent::writeValue($formatter, $recursor, $field->with(phpType: $phpType), $source);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return any(static fn (string $class) => $field->phpType === $class || is_subclass_of($field->phpType, $class))($this->supportedTypes);
    }
}
