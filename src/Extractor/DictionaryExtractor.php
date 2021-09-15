<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\JsonFormatter;

class DictionaryExtractor implements Extractor
{
    public function extract(
        JsonFormatter $formatter,
        string $format,
        string $name,
        mixed $value,
        string $type,
        mixed $runningValue
    ): mixed {
        // @todo Differentiate this from sequences.
        return $formatter->serializeArray($runningValue, $name, $value);
    }

    public function supportsExtract(string $type, mixed $value, string $format): bool
    {
        return $type === 'array' && !\array_is_list($value);
    }
}
