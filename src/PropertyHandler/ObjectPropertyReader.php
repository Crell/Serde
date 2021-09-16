<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

class ObjectPropertyReader implements PropertyWriter, PropertyReader
{
    public function readValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        return $formatter->serializeObject($runningValue, $field->serializedName(), $value, $recursor);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return is_object($value);
    }

    public function writeValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        return $formatter->deserializeObject($source, $field->serializedName(), $recursor, $field->phpType);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->phpType === 'object' || class_exists($field->phpType);
    }
}
