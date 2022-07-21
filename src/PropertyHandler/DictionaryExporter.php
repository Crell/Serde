<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\CollectionItem;
use Crell\Serde\Deserializer;
use Crell\Serde\Dict;
use Crell\Serde\InvalidArrayKeyType;
use Crell\Serde\KeyType;
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;

class DictionaryExporter implements Exporter, Importer
{
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        /** @var DictionaryField|null $typeField */
        $typeField = $field->typeField;

        if ($typeField?->shouldImplode()) {
            return $serializer->formatter->serializeString($runningValue, $field, $typeField->implode($value));
        }

        $dict = new Dict();
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
            $dict->items[] = new CollectionItem(field: $f, value: $v);
        }

        return $serializer->formatter->serializeDictionary($runningValue, $field, $dict, $serializer);
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === 'array' && !\array_is_list($value);
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

        return $field->phpType === 'array' && ($typeField === null || $typeField instanceof DictionaryField);
    }


}
