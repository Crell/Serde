<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

class DictionaryExtractor implements Extractor
{
    public function extract(
        JsonFormatter $formatter,
        string $format,
        mixed $value,
        Field $field,
        mixed $runningValue
    ): mixed {
        // @todo Differentiate this from sequences.
        return $formatter->serializeArray($runningValue, $field->serializedName(), $value);
    }

    public function supportsExtract(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === 'array' && !\array_is_list($value);
    }
}
