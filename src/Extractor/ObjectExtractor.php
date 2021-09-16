<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

class ObjectExtractor implements SerializerAware, Injector, Extractor
{
    use RecursiveExtractorTrait;

    public function extract(
        JsonFormatter $formatter,
        string $format,
        mixed $value,
        Field $field,
        mixed $runningValue
    ): mixed {
        return $formatter->serializeObject($runningValue, $field->serializedName(), $value, $this->serializer, $format);
    }

    public function supportsExtract(Field $field, mixed $value, string $format): bool
    {
        return is_object($value);
    }

    public function getValue(JsonFormatter $formatter, string $format, mixed $source, Field $field): mixed
    {
        return $formatter->deserializeObject($source, $field->serializedName(), $this->serializer, $format, $field->phpType);
    }

    public function supportsInject(Field $field, string $format): bool
    {
        return $field->phpType === 'object' || class_exists($field->phpType);
    }
}
