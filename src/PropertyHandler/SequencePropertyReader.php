<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\CollectionItem;
use Crell\Serde\Field;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\Sequence;

class SequencePropertyReader implements PropertyReader, PropertyWriter
{
    public function readValue(Formatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $seq = new Sequence();
        foreach ($value as $k => $v) {
            $f = Field::create(serializedName: uniqid('dummy_'), phpType: \get_debug_type($v));
            $seq->items[] = new CollectionItem(field: $f, value: $v);
        }

        return $formatter->serializeSequence($runningValue, $field, $seq, $recursor);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === 'array' && \array_is_list($value);
    }

    public function writeValue(Deformatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        return $formatter->deserializeSequence($source, $field, $recursor);
    }

    public function canWrite(Field $field, string $format): bool
    {
        // This is not good, as we cannot differentiate from dictionaries. :-(
        return $field->phpType === 'array';
    }
}

