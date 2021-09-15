<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\JsonFormatter;

class ScalarExtractor implements Extractor, Injector
{
    public function extract(
        JsonFormatter $formatter,
        string $format,
        string $name,
        mixed $value,
        string $type,
        mixed $runningValue
    ): mixed {
        return match ($type) {
            'int' => $formatter->serializeInt($runningValue, $name, $value),
            'float' => $formatter->serializeFloat($runningValue, $name, $value),
            'bool' => $formatter->serializeBool($runningValue, $name, $value),
            'string' => $formatter->serializeString($runningValue, $name, $value),
        };
    }

    public function getValue(JsonFormatter $formatter, string $format, mixed $source, string $name, string $type): mixed
    {
        return match ($type) {
            'int' => $formatter->deserializeInt($source, $name),
            'float' => $formatter->deserializeFloat($source, $name),
            'bool' => $formatter->deserializeBool($source, $name),
            'string' => $formatter->deserializeString($source, $name),
        };
    }


    public function supportsExtract(string $type, mixed $value, string $format): bool
    {
        return is_scalar($value);
    }

    public function supportsInject(string $type, string $format): bool
    {
        return in_array($type, ['int', 'float', 'bool', 'string']);
    }
}
