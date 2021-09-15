<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\JsonFormatter;

class ObjectExtractor implements SerializerAware, Injector, Extractor
{
    use RecursiveExtractorTrait;

    public function extract(
        JsonFormatter $formatter,
        string $format,
        string $name,
        mixed $value,
        string $type,
        mixed $runningValue
    ): mixed {
        return $formatter->serializeObject($runningValue, $name, $value, $this->serializer, $format);
    }

    public function supportsExtract(string $type, mixed $value, string $format): bool
    {
        return is_object($value);
    }

    public function getValue(JsonFormatter $formatter, string $format, mixed $source, string $name, string $type): mixed
    {
        return $formatter->deserializeObject($source, $name, $this->serializer, $format, $type);
    }

    public function supportsInject(string $type, string $format): bool
    {
        return $type === 'object' || class_exists($type);
    }


}
