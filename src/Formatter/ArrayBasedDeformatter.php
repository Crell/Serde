<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\ClassDef;
use Crell\Serde\Deserializer;
use Crell\Serde\Field;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeMap;
use function Crell\fp\reduceWithKeys;

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

    public function deserializeSequence(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }

        $class = $field?->typeField?->arrayType ?? '';
        if (class_exists($class) || interface_exists($class)) {
            return $this->upcastArray($decoded[$field->serializedName], $deserializer, $class);
        }

        return $this->upcastArray($decoded[$field->serializedName], $deserializer);
    }

    public function deserializeDictionary(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }
        // @todo Still unsure if this should be an exception instead.
        if (!is_array($decoded[$field->serializedName])) {
            return SerdeError::FormatError;
        }

        $class = $field?->typeField?->arrayType ?? '';
        if (class_exists($class) || interface_exists($class)) {
            return $this->upcastArray($decoded[$field->serializedName], $deserializer, $class);
        }

        return $this->upcastArray($decoded[$field->serializedName], $deserializer);
    }

    /**
     * Deserializes all elements of an array, through the recursor.
     */
    protected function upcastArray(array $data, Deserializer $deserializer, ?string $type = null): array
    {
        $upcast = function(array $ret, mixed $v, int|string $k) use ($deserializer, $type, $data) {
            $map = $type ? $deserializer->typeMapper->typeMapForClass($type) : null;
            $arrayType = $map?->findClass($v[$map?->keyField()]) ?? $type ?? get_debug_type($v);
            $f = Field::create(serializedName: "$k", phpType: $arrayType);
            $ret[$k] = $deserializer->deserialize($data, $f);
            return $ret;
        };

        return reduceWithKeys([], $upcast)($data);
    }

    /**
     * @param mixed $decoded
     * @param Field $field
     * @param Deserializer $deserializer
     * @return array|SerdeError
     */
    public function deserializeObject(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }
        // @todo Still unsure if this should be an exception instead.
        if (!is_array($decoded[$field->serializedName])) {
            return SerdeError::FormatError;
        }

        // Now that we have an array of the raw data, some values need to be
        // recursively upcast to objects themselves, based on the information
        // in the object metadata for the target object.

        $data = $decoded[$field->serializedName];

        $usedNames = [];
        $collectingArray = null;
        /** @var Field[] $collectingObjects */
        $collectingObjects = [];

        $ret = [];

        /** @var Field $propField */
        foreach ($deserializer->typeMapper->propertyList($field, $data) as $propField) {
            $usedNames[] = $propField->serializedName;
            if ($propField->flatten && $propField->typeCategory === TypeCategory::Array) {
                $collectingArray = $propField;
            } elseif ($propField->flatten && $propField->typeCategory === TypeCategory::Object) {
                $collectingObjects[] = $propField;
            } else {
                $ret[$propField->serializedName] = ($propField->typeCategory->isEnum() || $propField->typeCategory->isCompound())
                    ? $deserializer->deserialize($data, $propField)
                    : $data[$propField->serializedName] ?? SerdeError::Missing;
            }
        }

        // Any other values are for a collecting field, if any,
        // but may need to be upcast themselves.

        // First upcast any values that will become properties of a collecting object.
        $remaining = $this->getRemainingData($data, $usedNames);
        foreach ($collectingObjects as $collectingField) {
            $remaining = $this->getRemainingData($remaining, $usedNames);
            $nestedProps = $deserializer->typeMapper->propertyList($collectingField, $remaining);
            foreach ($nestedProps as $propField) {
                $ret[$propField->serializedName] = ($propField->typeCategory->isEnum() || $propField->typeCategory->isCompound())
                    ? $deserializer->deserialize($data, $propField)
                    : $remaining[$propField->serializedName] ?? SerdeError::Missing;
                $usedNames[] = $propField->serializedName;
            }
        }

        // Then IF the remaining data is going to be collected to an array,
        // and that array has a type map, upcast all elements of that array to
        // the appropriate type.
        $remaining = $this->getRemainingData($remaining, $usedNames);
        if ($collectingArray && $map = $deserializer->typeMapper->typeMapForField($collectingArray)) {
            foreach ($remaining as $k => $v) {
                $class = $map->findClass($v[$map->keyField()]);
                $ret[$k] = $deserializer->deserialize($remaining, Field::create(serializedName: "$k", phpType: $class));
            }
        } else {
            // Otherwise, just tack on whatever is left to the processed data.
            $ret = [...$ret, ...$remaining];
        }

        return $ret;
    }


    public function getRemainingData(mixed $source, array $used): array
    {
        return array_diff_key($source, array_flip($used));
    }
}
