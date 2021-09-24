<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

class DateTimeZonePropertyReader implements PropertyReader, PropertyWriter
{
    /**
     * @param JsonFormatter $formatter
     * @param callable $recursor
     * @param Field $field
     * @param \DateTimeZone $value
     * @param mixed $runningValue
     * @return mixed
     */
    public function readValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $string = $value->getName();
        return $formatter->serializeString($runningValue, $field, $string);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === \DateTimeZone::class;
    }

    public function writeValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        $string = $formatter->deserializeString($source, $field);

        return new \DateTimeZone($string);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->phpType === \DateTimeZone::class;
    }
}
