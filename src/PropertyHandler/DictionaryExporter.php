<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\CollectionItem;
use Crell\Serde\Deserializer;
use Crell\Serde\Dict;
use Crell\Serde\InvalidArrayKeyType;
use Crell\Serde\KeyType;
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;

class DictionaryExporter implements Exporter, Importer
{
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        /** @var DictionaryField|null $typeField */
        $typeField = $field->typeField;

        if ($typeField?->shouldImplode()) {
            return $serializer->formatter->serializeString($runningValue, $field, $typeField->implode($value));
        }

        $dict = is_array($value) ? $this->arrayToDict($value, $field) : $this->iterableToDict($value, $field);

        return $serializer->formatter->serializeDictionary($runningValue, $field, $dict, $serializer);
    }

    /**
     * @param array<mixed, mixed> $value
     */
    protected function iterableToDict(iterable $value, Field $field): Dict
    {
        $dict = new Dict((static function () use ($value, $field) {
            /** @var DictionaryField|null $typeField */
            $typeField = $field->typeField;
            foreach ($value as $k => $v) {
                // Most $runningValue implementations will be an array.
                // Arrays in PHP force-cast an integer-string key to
                // an integer.  That means we cannot guarantee the type
                // of the key going out in the Exporter. The Formatter
                // will have to do so, if it cares. However, we can still
                // detect and reject string-in-int.
                if ($typeField?->keyType === KeyType::Int && \get_debug_type($k) === 'string') {
                    // It's an int field, but the key is a string. That's a no-no.
                    throw InvalidArrayKeyType::create($field, 'string');
                }
                $f = Field::create(serializedName: "$k", phpType: \get_debug_type($v));
                yield new CollectionItem(field: $f, value: $v);
            }
        })());

        return $dict;
    }

    /**
     * @param array<mixed, mixed> $value
     */
    protected function arrayToDict(array $value, Field $field): Dict
    {
        /** @var DictionaryField|null $typeField */
        $typeField = $field->typeField;

        $items = [];
        foreach ($value as $k => $v) {
            // Most $runningValue implementations will be an array.
            // Arrays in PHP force-cast an integer-string key to
            // an integer.  That means we cannot guarantee the type
            // of the key going out in the Exporter. The Formatter
            // will have to do so, if it cares. However, we can still
            // detect and reject string-in-int.
            if ($typeField?->keyType === KeyType::Int && \get_debug_type($k) === 'string') {
                // It's an int field, but the key is a string. That's a no-no.
                throw InvalidArrayKeyType::create($field, 'string');
            }
            $f = Field::create(serializedName: "$k", phpType: \get_debug_type($v));
            $items[] = new CollectionItem(field: $f, value: $v);
        }

        return new Dict($items);
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return match (true) {
            $field->typeField instanceof DictionaryField => true,
            $field->typeField instanceof SequenceField => false,
            $field->phpType === 'array' && !array_is_list($value) => true,
            default => false,
        };
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        /** @var DictionaryField $typeField */
        $typeField = $field->typeField;
        // The extra type check is necessary because it might be a SequenceField.
        // We cannot easily tell them apart at the moment.
        if ($typeField instanceof DictionaryField && $typeField->implodeOn) {
            $val = $deserializer->deformatter->deserializeString($source, $field);
            // This is already an exhaustive match, but PHPStan doesn't know that.
            // @phpstan-ignore-next-line
            return $val === SerdeError::Missing ? null : $typeField->explode($val);
        }

        return $deserializer->deformatter->deserializeDictionary($source, $field, $deserializer);
    }

    public function canImport(Field $field, string $format): bool
    {
        $typeField = $field->typeField;

        return ($field->phpType === 'array' && ($typeField === null || $typeField instanceof DictionaryField))
            || ($field->typeCategory === TypeCategory::Generator && $field->typeField instanceof DictionaryField);
    }
}
