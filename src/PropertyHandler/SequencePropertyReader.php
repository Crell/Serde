<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\CollectionItem;
use Crell\Serde\DictionaryField;
use Crell\Serde\Field;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\Sequence;
use Crell\Serde\SequenceField;
use Crell\Serde\SerdeError;

class SequencePropertyReader implements PropertyReader, PropertyWriter
{
    public function readValue(Formatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        /** @var ?SequenceField $typeField */
        $typeField = $field?->typeField;
        if ($typeField?->shouldImplode()) {
            return $formatter->serializeString($runningValue, $field, $typeField->implode($value));
        }

        $seq = new Sequence();
        foreach ($value as $k => $v) {
            $f = Field::create(serializedName: "$k", phpType: \get_debug_type($v));
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
        /** @var ?SequenceField $typeField */
        $typeField = $field?->typeField;
        // The extra type check is necessary because it might be a DictionaryField.
        // We cannot easily tell them apart at the moment.
        if ($typeField instanceof SequenceField && $typeField?->implodeOn) {
            $val = $formatter->deserializeString($source, $field);
            return $val === SerdeError::Missing
                ? null
                : $typeField->explode($val);
        }

        return $formatter->deserializeSequence($source, $field, $recursor);
    }

    public function canWrite(Field $field, string $format): bool
    {
        $typeField = $field?->typeField;
        // This may still catch a dictionary that is unmarked. That is unavoidable.
        // Fortunately it doesn't break in practice because PHP doesn't care.
        return $field->phpType === 'array' && ($typeField === null || $typeField instanceof SequenceField);
    }
}

