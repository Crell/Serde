<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\CollectionItem;
use Crell\Serde\Deserializer;
use Crell\Serde\Dict;
use Crell\Serde\DictionaryField;
use Crell\Serde\Field;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\SequenceField;
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;

class DictionaryPropertyReader implements PropertyReader, PropertyWriter
{
    public function readValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        /** @var ?DictionaryField $typeField */
        $typeField = $field?->typeField;
        if ($typeField?->shouldImplode()) {
            return $serializer->formatter->serializeString($runningValue, $field, $typeField->implode($value));
        }

        $dict = new Dict();
        foreach ($value as $k => $v) {
            $f = Field::create(serializedName: $k, phpType: \get_debug_type($v));
            $dict->items[] = new CollectionItem(field: $f, value: $v);
        }

        return $serializer->formatter->serializeDictionary($runningValue, $field, $dict, $serializer->serialize(...));
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === 'array' && !\array_is_list($value);
    }

    public function writeValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        /** @var ?DictionaryField $typeField */
        $typeField = $field?->typeField;
        // The extra type check is necessary because it might be a SequenceField.
        // We cannot easily tell them apart at the moment.
        if ($typeField instanceof DictionaryField && $typeField?->implodeOn) {
            $val = $deserializer->deformatter->deserializeString($source, $field);
            return $val === SerdeError::Missing
                ? null
                : $typeField->explode($val);
        }

        return $deserializer->deformatter->deserializeDictionary($source, $field, $deserializer->deserialize(...));
    }

    public function canWrite(Field $field, string $format): bool
    {
        $typeField = $field?->typeField;

        return $field->phpType === 'array' && ($typeField === null || $typeField instanceof DictionaryField);
    }


}
