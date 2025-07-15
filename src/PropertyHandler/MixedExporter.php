<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\AttributeUtils\TypeComplexity;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\MixedField;
use Crell\Serde\Attributes\UnionField;
use Crell\Serde\UnableToDeriveTypeOnMixedField;
use Crell\Serde\Deserializer;
use Crell\Serde\Formatter\SupportsTypeIntrospection;
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
        $type = $this->deriveType($deserializer, $field, $source)
            ?? throw UnableToDeriveTypeOnMixedField::create($deserializer->deformatter, $field);

        // Folding UnionField in here is not ideal, but it means we don't have to
        // worry about ordering a UnionExporter vs this one, and this is the only
        // difference between the fields.
        $subTypeField = ($field->typeField instanceof UnionField)
            ? $field->typeField->typeFields[$type] ?? null
            : null;

        return $deserializer->deserialize($source, Field::create(
            serializedName: $field->serializedName,
            phpType: $type,
            typeField: $subTypeField,
        ));
    }

    /**
     * Determines the type of the incoming value.
     *
     * If the MixedField attribute specifies a preferred type, that takes precedence.
     * If the deformatter is able to determine it for us, that will be trusted.
     * If it's a union type, we'll make an educated guess that it's the first class listed.
     */
    protected function deriveType(Deserializer $deserializer, Field $field, mixed $source): ?string
    {
        $type = null;

        if ($deserializer->deformatter instanceof SupportsTypeIntrospection) {
            $type = $deserializer->deformatter->getType($source, $field);
        }

        /** @var MixedField|null $typeField */
        $typeField = $field->typeField;

        if ($typeField && ($type === 'array' || $type === null) && class_exists($typeField->suggestedType())) {
            // If the data is an array or unspecified,
            // and a suggested type is specified, assume the specified type.
            return $typeField->suggestedType();
        }
        if (class_exists($type) || interface_exists($type)) {
            // The deformatter already determined what class it should be. Trust it.
            return $type;
        }
        if ($field->typeDef->complexity === TypeComplexity::Union && ($type === 'array' || $type == null)) {
            // If it's a union type, and the incoming data is an array, and one of the
            // listed types is a class, we can deduce that is probably what it should
            // be deserialized into.  If multiple classes are specified, the first
            // will be used.  If that's not desired, specify a suggested type via attribute.
            foreach ($field->typeDef->getUnionTypes() as $t) {
                if (class_exists($t) || interface_exists($t)) {
                    return $t;
                }
            }
        }

        return $type;
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Mixed && $field->typeDef->accepts(get_debug_type($value));
    }

    public function canImport(Field $field, string $format): bool
    {
        // @todo In 2.0, change the API to pass the full Deformatter, not just the format string,
        //   so that we can check against the SupportsTypeIntrospection interface instead of a hard coded list.
        return $field->typeCategory === TypeCategory::Mixed && in_array($format, ['json', 'yaml', 'array', 'toml']);
    }
}
