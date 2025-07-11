<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\MixedField;
use Crell\Serde\CollectionItem;
use Crell\Serde\Deserializer;
use Crell\Serde\Dict;
use Crell\Serde\InvalidArrayKeyType;
use Crell\Serde\KeyType;
use Crell\Serde\Sequence;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;

/**
 * Exporter/importer for `mixed` properties.
 *
 * This class makes a good-faith attempt to detect the type of a given field by its value.
 * On import, it currently works only on array-based formats (JSON, YAML, TOML, etc.)
 *
 * To deserialize into an object, the property must have the MixedField attribute.
 *
 * @see MixedField
 */
class MixedExporter implements Importer, Exporter
{
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        // We need to bypass the circular reference check in Serializer::serialize(),
        // or else an object would always fail here.
        return $serializer->doSerialize($value, $runningValue, Field::create(
            serializedName: $field->serializedName,
            phpType: \get_debug_type($value),
        ));
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        // This is normally a bad idea, as the $source should be opaque. In this
        // case, we're guaranteed that the $source is array-based, so we can introspect
        // it directly.
        $type = \get_debug_type($source[$field->serializedName]);

        /** @var MixedField|null $typeField */
        $typeField = $field->typeField;
        if ($typeField && class_exists($typeField->suggestedType) && $type === 'array') {
            $type = $typeField->suggestedType;
        }

        return $deserializer->deserialize($source, Field::create(
            serializedName: $field->serializedName,
            phpType: $type,
        ));
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Mixed;
    }

    public function canImport(Field $field, string $format): bool
    {
        // We can only import if we know that the $source will be an array so that it
        // can be introspected.  If it's not, then this class has no way to tell what
        // type to tell the Deformatter to read.
        // @todo In 2.0, change the API to pass the full Deformatter, not just the format string,
        //   so that we can check against an ArrayBased interface instead of a hard coded list.
        return $field->typeCategory === TypeCategory::Mixed && in_array($format, ['json', 'yaml', 'array', 'toml']);
    }
}
