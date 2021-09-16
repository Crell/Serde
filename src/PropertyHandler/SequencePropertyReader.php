<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

class SequencePropertyReader implements PropertyReader, PropertyWriter
{
    public function readValue(
        JsonFormatter $formatter,
        string $format,
        mixed $value,
        Field $field,
        mixed $runningValue
    ): mixed {
        return $formatter->serializeArray($runningValue, $field->serializedName(), $value);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === 'array' && \array_is_list($value);
    }

    public function writeValue(JsonFormatter $formatter, string $format, mixed $source, Field $field): mixed
    {
        return $formatter->deserializeArray($source, $field->serializedName());
    }

    public function canWrite(Field $field, string $format): bool
    {
        // This is not good, as we cannot differentiate from dictionaries. :-(
        return $field->phpType === 'array';
    }


}
