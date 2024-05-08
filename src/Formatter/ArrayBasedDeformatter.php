<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;
use Crell\Serde\FormatParseError;
use Crell\Serde\DeformatterResult;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeMismatch;
use Crell\Serde\ValueType;
use function Crell\fp\first;
use function Crell\fp\pipe;
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
    public function deserializeInt(mixed $decoded, Field $field): int|DeformatterResult|null
    {
        if (!array_key_exists($field->serializedName, $decoded)) {
            return DeformatterResult::Missing;
        }

        $value = $decoded[$field->serializedName];

        if ($field->strict) {
            if (!is_int($value) && !($field->nullable && is_null($value))) {
                throw TypeMismatch::create($field->serializedName, 'int', \get_debug_type($decoded[$field->serializedName]));
            }
            return $decoded[$field->serializedName];
        }

        // Weak mode.
        return (int)($decoded[$field->serializedName]);
    }

    public function deserializeFloat(mixed $decoded, Field $field): float|DeformatterResult|null
    {
        if (!array_key_exists($field->serializedName, $decoded)) {
            return DeformatterResult::Missing;
        }

        $value = $decoded[$field->serializedName];

        if ($field->strict) {
            if (!is_int($value) && !is_float($value) && !($field->nullable && is_null($value))) {
                throw TypeMismatch::create($field->serializedName, 'float', \get_debug_type($decoded[$field->serializedName]));
            }
            return $decoded[$field->serializedName];
        }

        // Weak mode.
        return (float)($decoded[$field->serializedName]);
    }

    public function deserializeBool(mixed $decoded, Field $field): bool|DeformatterResult|null
    {
        if (!array_key_exists($field->serializedName, $decoded)) {
            return DeformatterResult::Missing;
        }

        $value = $decoded[$field->serializedName];

        if ($field->strict) {
            if (!is_bool($value) && !($field->nullable && is_null($value))) {
                throw TypeMismatch::create($field->serializedName, 'bool', \get_debug_type($decoded[$field->serializedName]));
            }
            return $decoded[$field->serializedName];
        }

        // Weak mode.
        return (bool)($decoded[$field->serializedName]);
    }

    public function deserializeString(mixed $decoded, Field $field): string|DeformatterResult|null
    {
        if (!array_key_exists($field->serializedName, $decoded)) {
            return DeformatterResult::Missing;
        }

        $value = $decoded[$field->serializedName];

        if ($field->strict) {
            if (!is_string($value) && !($field->nullable && is_null($value))) {
                throw TypeMismatch::create($field->serializedName, 'string', \get_debug_type($value));
            }
            return $value;
        }

        // Weak mode.
        return (string)($value);
    }

    public function deserializeNull(mixed $decoded, Field $field): ?DeformatterResult
    {
        // isset() returns false for null, so we cannot use that. Thanks, PHP.
        if (!array_key_exists($field->serializedName, $decoded)) {
            return DeformatterResult::Missing;
        }

        // Strict and weak mode are the same here; null must always be null.
        if (!is_null($decoded[$field->serializedName])) {
            throw TypeMismatch::create($field->serializedName, 'null', \get_debug_type($decoded[$field->serializedName]));
        }

        return $decoded[$field->serializedName];
    }

    public function deserializeSequence(mixed $decoded, Field $field, Deserializer $deserializer): array|DeformatterResult|null
    {
        // isset() returns false for null, so we cannot use that. Thanks, PHP.
        if (!array_key_exists($field->serializedName, $decoded)) {
            return DeformatterResult::Missing;
        }

        if ($decoded[$field->serializedName] === null) {
            return null;
        }

        $data = $decoded[$field->serializedName];

        if (!is_array($data)) {
            throw TypeMismatch::create($field->serializedName, 'array (sequence)', \get_debug_type($decoded[$field->serializedName]));
        }

        if (!array_is_list($data)) {
            if ($field->strict) {
                throw TypeMismatch::create($field->serializedName, 'array (sequence)', 'associative array');
            }
            $data = array_values($data);
        }

        // This line is fine, because if typeField is somehow not of a type with an
        // arrayType property, it resolves to null anyway, which is exactly what we want.
        // @phpstan-ignore-next-line
        $class = $field?->typeField?->arrayType ?? '';
        if ($class instanceof ValueType) {
            if ($class->assert($data)) {
                return $data;
            } else {
                throw TypeMismatch::create($field->serializedName, "array($class->name)", "array(" . \get_debug_type($data[0] . ')'));
            }
        }
        else if (class_exists($class) || interface_exists($class)) {
            return $this->upcastArray($data, $deserializer, $class);
        } else {
            return $this->upcastArray($data, $deserializer);
        }
    }

    public function deserializeDictionary(mixed $decoded, Field $field, Deserializer $deserializer): array|DeformatterResult|null
    {
        // isset() returns false for null, so we cannot use that. Thanks, PHP.
        if (!array_key_exists($field->serializedName, $decoded)) {
            return DeformatterResult::Missing;
        }

        if ($decoded[$field->serializedName] === null) {
            return null;
        }

        if (!is_array($decoded[$field->serializedName])) {
            throw FormatParseError::create($field, $this->format(), $decoded);
        }

        $data = $decoded[$field->serializedName];

        // This line is fine, because if typeField is somehow not of a type with an
        // arrayType property, it resolves to null anyway, which is exactly what we want.
        // @phpstan-ignore-next-line
        $class = $field?->typeField?->arrayType ?? '';
        if ($class instanceof ValueType) {
            if ($class->assert($data)) {
                return $data;
            } else {
                throw TypeMismatch::create($field->serializedName, "array($class->name)", "array(" . \get_debug_type($data[array_key_first($data)] . ')'));
            }
        }
        else if (class_exists($class) || interface_exists($class)) {
            return $this->upcastArray($data, $deserializer, $class);
        } else {
            return $this->upcastArray($data, $deserializer);
        }
    }

    /**
     * Deserializes all elements of an array, through the recursor.
     *
     * @param array<int|string, mixed> $data
     * @param Deserializer $deserializer
     * @param string|null $type
     * @return array<string|int, mixed>
     */
    protected function upcastArray(array $data, Deserializer $deserializer, ?string $type = null): array
    {
        $upcast = function(array $ret, mixed $v, int|string $k) use ($deserializer, $type, $data) {
            $map = $type ? $deserializer->typeMapper->typeMapForClass($type) : null;
            $arrayType = $map?->findClass($v[$map->keyField()]) ?? $type ?? get_debug_type($v);
            $f = Field::create(serializedName: "$k", phpType: $arrayType);
            $ret[$k] = $deserializer->deserialize($data, $f);
            return $ret;
        };

        return reduceWithKeys([], $upcast)($data);
    }

    /**
     * @param array<string, mixed> $decoded
     * @param Field $field
     * @param Deserializer $deserializer
     * @return array<string, mixed>|DeformatterResult
     */
    public function deserializeObject(mixed $decoded, Field $field, Deserializer $deserializer): array|DeformatterResult|null
    {
        $candidateNames = [$field->serializedName, ...$field->alias];

        $key = pipe($candidateNames,
            first(static fn (string $name): bool => isset($decoded[$name]))
        );

        if (!array_key_exists($key, $decoded)) {
            return DeformatterResult::Missing;
        }

        if (!is_array($decoded[$key])) {
            throw FormatParseError::create($field, $this->format(), $decoded);
        }

        $data = $decoded[$key];

        // Now that we have an array of the raw data, some values need to be
        // recursively upcast to objects themselves, based on the information
        // in the object metadata for the target object.

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
            } elseif (array_key_exists($propField->serializedName, $data)) {
                $ret[$propField->serializedName] = $deserializer->deserialize($data, $propField);
            } else {
                $key = pipe(
                    $propField->alias,
                    first(fn(string $name): bool => array_key_exists($name, $data)),
                );
                $ret[$propField->serializedName] = $key
                    ? $deserializer->deserialize($data, $propField->with(serializedName: $key))
                    : DeformatterResult::Missing;
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
                // All values need to be sent through the full pipeline, even primitive ones,
                // so that their types can be treated the same as non-collected properties.
                $ret[$propField->serializedName] = $deserializer->deserialize($data, $propField);
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
        } elseif ($remaining) {
            // Otherwise, just tack on whatever is left to the processed data.
            $ret = [...$ret, ...$remaining];
        }

        return $ret;
    }

    /**
     * @param mixed $source
     * @param string[] $used
     * @return array<string, mixed>
     */
    public function getRemainingData(mixed $source, array $used): array
    {
        return array_diff_key($source, array_flip($used));
    }
}
