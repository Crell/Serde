<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

class ScalarPropertyReader implements PropertyReader, PropertyWriter
{
    public function readValue(
        JsonFormatter $formatter,
        callable $recursor,
        Field $field,
        mixed $value,
        mixed $runningValue
    ): mixed {
        return match ($field->phpType) {
            'int' => $formatter->serializeInt($runningValue, $field->serializedName(), $value),
            'float' => $formatter->serializeFloat($runningValue, $field->serializedName(), $value),
            'bool' => $formatter->serializeBool($runningValue, $field->serializedName(), $value),
            'string' => $formatter->serializeString($runningValue, $field->serializedName(), $value),
        };
    }

    public function writeValue(JsonFormatter $formatter, callable $recursor, mixed $source, Field $field): mixed
    {
        return match ($field->phpType) {
            'int' => $formatter->deserializeInt($source, $field->serializedName()),
            'float' => $formatter->deserializeFloat($source, $field->serializedName()),
            'bool' => $formatter->deserializeBool($source, $field->serializedName()),
            'string' => $formatter->deserializeString($source, $field->serializedName()),
        };
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return is_scalar($value);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return in_array($field->phpType, ['int', 'float', 'bool', 'string']);
    }
}
