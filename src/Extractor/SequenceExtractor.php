<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\JsonFormatter;

class SequenceExtractor implements Extractor, Injector
{
    public function extract(
        JsonFormatter $formatter,
        string $format,
        string $name,
        mixed $value,
        string $type,
        mixed $runningValue
    ): mixed {
        return $formatter->serializeArray($runningValue, $name, $value);
    }

    public function supportsExtract(string $type, mixed $value, string $format): bool
    {
        return $type === 'array' && \array_is_list($value);
    }

    public function getValue(JsonFormatter $formatter, string $format, mixed $source, string $name, string $type): mixed
    {
        return $formatter->deserializeArray($source, $name);
    }

    public function supportsInject(string $type, string $format): bool
    {
        // This is not good, as we cannot differentiate from dictionaries. :-(
        return $type === 'array';
    }


}
