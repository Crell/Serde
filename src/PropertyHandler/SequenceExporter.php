<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\CollectionItem;
use Crell\Serde\Deserializer;
use Crell\Serde\Sequence;
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;

class SequenceExporter implements Exporter, Importer
{
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        /** @var ?SequenceField $typeField */
        $typeField = $field->typeField;
        if ($typeField?->shouldImplode()) {
            return $serializer->formatter->serializeString($runningValue, $field, $typeField->implode($value));
        }

        $seq = is_array($value) ? $this->arrayToSequence($value) : $this->iterableToSequence($value);

        return $serializer->formatter->serializeSequence($runningValue, $field, $seq, $serializer);
    }

    /**
     * @param mixed[] $value
     */
    protected function iterableToSequence(iterable $value): Sequence
    {
        $seq = new Sequence();

        $seq->items = (static function () use ($value) {
            foreach ($value as $k => $v) {
                $f = Field::create(serializedName: "$k", phpType: \get_debug_type($v));
                yield new CollectionItem(field: $f, value: $v);
            }
        })();
        return $seq;
    }

    /**
     * @param array<mixed> $value
     */
    protected function arrayToSequence(array $value): Sequence
    {
        $items = [];
        foreach ($value as $k => $v) {
            $f = Field::create(serializedName: "$k", phpType: \get_debug_type($v));
            $items[] = new CollectionItem(field: $f, value: $v);
        }
        return new Sequence($items);
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return match (true) {
            $field->typeField instanceof SequenceField => true,
            $field->typeField instanceof DictionaryField => false,
            $field->phpType === 'array' && array_is_list($value) => true,
            default => false,
        };
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        /** @var ?SequenceField $typeField */
        $typeField = $field->typeField;
        // The extra type check is necessary because it might be a DictionaryField.
        // We cannot easily tell them apart at the moment.
        if ($typeField instanceof SequenceField && $typeField->implodeOn) {
            $val = $deserializer->deformatter->deserializeString($source, $field);
            return $val === SerdeError::Missing ? null : $typeField->explode($val);
        }

        return $deserializer->deformatter->deserializeSequence($source, $field, $deserializer);
    }

    public function canImport(Field $field, string $format): bool
    {
        $typeField = $field->typeField;
        // This may still catch a dictionary that is unmarked. That is unavoidable.
        // Fortunately it doesn't break in practice because PHP doesn't care.
        return ($field->phpType === 'array' && ($typeField === null || $typeField instanceof SequenceField))
            || ($field->typeCategory === TypeCategory::Generator && $field->typeField instanceof SequenceField);
    }
}

