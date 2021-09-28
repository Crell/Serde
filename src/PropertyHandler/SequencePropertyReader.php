<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;

class SequencePropertyReader implements PropertyReader, PropertyWriter
{
    public function readValue(Formatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        return $formatter->serializeArray($runningValue, $field, $value, $recursor);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === 'array' && \array_is_list($value);
    }

    public function writeValue(Deformatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        return $formatter->deserializeArray($source, $field, $recursor);
    }

    public function canWrite(Field $field, string $format): bool
    {
        // This is not good, as we cannot differentiate from dictionaries. :-(
        return $field->phpType === 'array';
    }
}
