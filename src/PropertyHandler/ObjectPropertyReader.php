<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

class ObjectPropertyReader implements SerializerAware, PropertyWriter, PropertyReader
{
    use RecursivePropertyHandler;

    public function readValue(
        JsonFormatter $formatter,
        string $format,
        mixed $value,
        Field $field,
        mixed $runningValue
    ): mixed {
        return $formatter->serializeObject($runningValue, $field->serializedName(), $value, $this->serializer, $format);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return is_object($value);
    }

    public function writeValue(JsonFormatter $formatter, string $format, mixed $source, Field $field): mixed
    {
        return $formatter->deserializeObject($source, $field->serializedName(), $this->serializer, $format, $field->phpType);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->phpType === 'object' || class_exists($field->phpType);
    }
}
