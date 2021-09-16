<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

class DictionaryPropertyReader implements PropertyReader
{
    public function readValue(
        JsonFormatter $formatter,
        string $format,
        mixed $value,
        Field $field,
        mixed $runningValue
    ): mixed {
        // @todo Differentiate this from sequences.
        return $formatter->serializeArray($runningValue, $field->serializedName(), $value);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === 'array' && !\array_is_list($value);
    }
}
