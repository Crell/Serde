<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

class SequenceExtractor implements Extractor, Injector
{
    public function extract(
        JsonFormatter $formatter,
        string $format,
        mixed $value,
        Field $field,
        mixed $runningValue
    ): mixed {
        return $formatter->serializeArray($runningValue, $field->serializedName(), $value);
    }

    public function supportsExtract(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === 'array' && \array_is_list($value);
    }

    public function getValue(JsonFormatter $formatter, string $format, mixed $source, Field $field): mixed
    {
        return $formatter->deserializeArray($source, $field->serializedName());
    }

    public function supportsInject(Field $field, string $format): bool
    {
        // This is not good, as we cannot differentiate from dictionaries. :-(
        return $field->phpType === 'array';
    }


}
