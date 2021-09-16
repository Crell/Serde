<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

class DateTimeExtractor implements Extractor, Injector
{
    /**
     * @param JsonFormatter $formatter
     * @param string $format
     * @param \DateTimeInterface $value
     * @param Field $field
     * @param mixed $runningValue
     * @return mixed
     */
    public function extract(
        JsonFormatter $formatter,
        string $format,
        mixed $value,
        Field $field,
        mixed $runningValue
    ): mixed {
        $string = $value->format(\DateTimeInterface::RFC3339_EXTENDED);
        return $formatter->serializeString($runningValue, $field->serializedName(), $string);
    }

    public function supportsExtract(Field $field, mixed $value, string $format): bool
    {
        return $value instanceof \DateTimeInterface;
    }

    public function getValue(JsonFormatter $formatter, string $format, mixed $source, Field $field): mixed
    {
        $string = $formatter->deserializeString($source, $field->serializedName());

        return new ($field->phpType)($string);
    }

    public function supportsInject(Field $field, string $format): bool
    {
        return in_array($field->phpType, [\DateTimeInterface::class, \DateTime::class, \DateTimeImmutable::class]);
    }
}
