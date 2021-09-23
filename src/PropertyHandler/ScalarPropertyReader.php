<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;
use Crell\Serde\TypeCategory;

class ScalarPropertyReader implements PropertyReader, PropertyWriter
{
    public function readValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        return match ($field->phpType) {
            'int' => $formatter->serializeInt($runningValue, $field, $value),
            'float' => $formatter->serializeFloat($runningValue, $field, $value),
            'bool' => $formatter->serializeBool($runningValue, $field, $value),
            'string' => $formatter->serializeString($runningValue, $field, $value),
        };
    }

    public function writeValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        return match ($field->phpType) {
            'int' => $formatter->deserializeInt($source, $field),
            'float' => $formatter->deserializeFloat($source, $field),
            'bool' => $formatter->deserializeBool($source, $field),
            'string' => $formatter->deserializeString($source, $field),
        };
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Scalar;
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Scalar;
    }
}
