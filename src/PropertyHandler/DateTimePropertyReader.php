<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

class DateTimePropertyReader implements PropertyReader, PropertyWriter
{
    /**
     * @param JsonFormatter $formatter
     * @param string $format
     * @param \DateTimeInterface $value
     * @param Field $field
     * @param mixed $runningValue
     * @return mixed
     */
    public function readValue(
        JsonFormatter $formatter,
        string $format,
        mixed $value,
        Field $field,
        mixed $runningValue
    ): mixed {
        $string = $value->format(\DateTimeInterface::RFC3339_EXTENDED);
        return $formatter->serializeString($runningValue, $field->serializedName(), $string);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $value instanceof \DateTimeInterface;
    }

    public function writeValue(JsonFormatter $formatter, string $format, mixed $source, Field $field): mixed
    {
        $string = $formatter->deserializeString($source, $field->serializedName());

        return new ($field->phpType)($string);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return in_array($field->phpType, [\DateTimeInterface::class, \DateTime::class, \DateTimeImmutable::class]);
    }
}
