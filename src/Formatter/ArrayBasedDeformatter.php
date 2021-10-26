<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\Field;
use Crell\Serde\NoTypeMapDefinedForKey;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeMapper;

/**
 * Utility implementations for array-based formats.
 *
 * Formats that work by first converting the serialized format to/from an
 * array can use this trait to handle creating the array bits, then
 * implement the initialize/finalize logic specific for that format.
 */
trait ArrayBasedDeformatter
{
    public function deserializeInt(mixed $decoded, Field $field): int|SerdeError
    {
        return $decoded[$field->serializedName] ?? SerdeError::Missing;
    }

    public function deserializeFloat(mixed $decoded, Field $field): float|SerdeError
    {
        return $decoded[$field->serializedName] ?? SerdeError::Missing;
    }

    public function deserializeBool(mixed $decoded, Field $field): bool|SerdeError
    {
        return $decoded[$field->serializedName] ?? SerdeError::Missing;
    }

    public function deserializeString(mixed $decoded, Field $field): string|SerdeError
    {
        return $decoded[$field->serializedName] ?? SerdeError::Missing;
    }

    public function deserializeSequence(mixed $decoded, Field $field, callable $recursor): array|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }

        if ($field->arrayType && class_exists($field->arrayType)) {
            $ret = [];
            foreach ($decoded[$field->serializedName] as $k => $v) {
                $f = Field::create(serializedName: "$k", phpType: $field->arrayType);
                $ret[$k] = $recursor($decoded[$field->serializedName], $f);
            }
            return $ret;
        }

        return $decoded[$field->serializedName];
    }

    public function deserializeDictionary(mixed $decoded, Field $field, callable $recursor): array|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }
        // @todo Still unsure if this should be an exception instead.
        if (!is_array($decoded[$field->serializedName])) {
            return SerdeError::FormatError;
        }

        $data = $decoded[$field->serializedName];

        $ret = [];
        foreach ($data as $k => $v) {
            $f = Field::create(serializedName: $k, phpType: get_debug_type($v));
            $ret[$k] = $recursor($data, $f);
        }

        return $ret;
    }

    /**
     * @param mixed $decoded
     * @param Field $field
     * @param callable $recursor
     * @param TypeMapper|null $typeMap
     * @return array|SerdeError
     */
    public function deserializeObject(mixed $decoded, Field $field, callable $recursor, ?TypeMapper $typeMap): array|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }
        // @todo Still unsure if this should be an exception instead.
        if (!is_array($decoded[$field->serializedName])) {
            return SerdeError::FormatError;
        }

        $data = $decoded[$field->serializedName];

        $collectingField = null;
        $usedNames = [];

        $ret = [];

        $properties = $this->propertyList($field, $typeMap, $data);

        // First pull out the properties we know about.
        /** @var Field $prop */
        foreach ($properties as $prop) {
            $usedNames[] = $prop->serializedName;
            if ($prop->flatten) {
                $collectingField = $prop;
                continue;
            }
            $ret[$prop->serializedName] = ($prop->typeCategory->isEnum() || $prop->typeCategory->isCompound())
                ? $recursor($data, $prop)
                : $data[$prop->serializedName] ?? SerdeError::Missing;
        }

        // Any other values are for a collecting field, if any,
        // but may need further processing according to the collecting field.
        $remaining = $this->getRemainingData($data, $usedNames);
        // Object collecting doesn't support type maps, so can be handled by
        // the generic version in the else clause.
        if ($collectingField?->phpType === 'array' && $collectingField?->typeMap) {
            foreach ($remaining as $k => $v) {
                $class = $collectingField->typeMap->findClass($v[$collectingField->typeMap->keyField()]);
                $ret[$k] = $recursor($remaining, Field::create(serializedName: "$k", phpType: $class));
            }
        } else {
            foreach ($remaining as $k => $v) {
                $ret[$k] = $v;
            }
        }

        return $ret;
    }

    /**
     * Gets the property list for a given object.
     *
     * We need to know the object properties to deserialize to.
     * However, that list may be modified by the type map, as
     * the type map is in the incoming data.
     * The key field is kept in the data so that the property writer
     * can also look up the right type.
     */
    protected function propertyList(Field $field, ?TypeMapper $map, array $data): array
    {
        $class = $map
            ? ($map->findClass($data[$map->keyField()])
                ?? throw NoTypeMapDefinedForKey::create($map->keyField(), $field->phpName ?? $field->phpType))
            : $field->phpType;

        return $this->getAnalyzer()->analyze($class, ClassDef::class)->properties;
    }

    public function getRemainingData(mixed $source, array $used): array
    {
        return array_diff_key($source, array_flip($used));
    }

    /**
     * Returns a class analyzer.
     *
     * Classes using this trait must provide a class analyzer via this method.
     *
     * @return ClassAnalyzer
     */
    abstract protected function getAnalyzer(): ClassAnalyzer;
}
