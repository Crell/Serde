<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;
use Crell\Serde\FormatParseError;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeMismatch;
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
    public function deserializeInt(mixed $decoded, Field $field): int|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }

        if ($field->strict) {
            if (!is_int($decoded[$field->serializedName])) {
                throw TypeMismatch::create($field->serializedName, 'int', \get_debug_type($decoded[$field->serializedName]));
            }
            return $decoded[$field->serializedName];
        }

        // Weak mode.
        return (int)($decoded[$field->serializedName]);
    }

    public function deserializeFloat(mixed $decoded, Field $field): float|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }

        if ($field->strict) {
            if (!(is_float($decoded[$field->serializedName]) || is_int($decoded[$field->serializedName]))) {
                throw TypeMismatch::create($field->serializedName, 'float', \get_debug_type($decoded[$field->serializedName]));
            }
            return $decoded[$field->serializedName];
        }

        // Weak mode.
        return (float)($decoded[$field->serializedName]);
    }

    public function deserializeBool(mixed $decoded, Field $field): bool|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }

        if ($field->strict) {
            if (!is_bool($decoded[$field->serializedName])) {
                throw TypeMismatch::create($field->serializedName, 'bool', \get_debug_type($decoded[$field->serializedName]));
            }
            return $decoded[$field->serializedName];
        }

        // Weak mode.
        return (bool)($decoded[$field->serializedName]);
    }

    public function deserializeString(mixed $decoded, Field $field): string|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }

        if ($field->strict) {
            if (!is_string($decoded[$field->serializedName])) {
                throw TypeMismatch::create($field->serializedName, 'string', \get_debug_type($decoded[$field->serializedName]));
            }
            return $decoded[$field->serializedName];
        }

        // Weak mode.
        return (string)($decoded[$field->serializedName]);
    }

    public function deserializeSequence(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }

        // This line is fine, because if typeField is somehow not of a type with an
        // arrayType property, it resolves to null anyway, which is exactly what we want.
        // @phpstan-ignore-next-line
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

        if (!is_array($decoded[$field->serializedName])) {
            throw FormatParseError::create($field, $this->format(), $decoded);
        }

        // This line is fine, because if typeField is somehow not of a type with an
        // arrayType property, it resolves to null anyway, which is exactly what we want.
        // @phpstan-ignore-next-line
        $class = $field?->typeField?->arrayType ?? '';
        if (class_exists($class) || interface_exists($class)) {
            return $this->upcastArray($decoded[$field->serializedName], $deserializer, $class);
        }

        return $this->upcastArray($decoded[$field->serializedName], $deserializer);
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
     * @return array<string, mixed>|SerdeError
     */
    public function deserializeObject(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError
    {
        $candidateNames = [$field->serializedName, ...$field->alias];

        $key = pipe($candidateNames,
            first(static fn (string $name): bool => isset($decoded[$name]))
        );

        if (!isset($decoded[$key])) {
            return SerdeError::Missing;
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
            } elseif (isset($data[$propField->serializedName])) {
                $ret[$propField->serializedName] = $deserializer->deserialize($data, $propField) ?? SerdeError::Missing;
            } else {
                $key = pipe(
                    $propField->alias,
                    first(fn(string $name): bool => isset($data[$name])),
                );
                $ret[$propField->serializedName] = $key
                    ? $deserializer->deserialize($data, $propField->with(serializedName: $key))
                    : SerdeError::Missing;
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
        } else {
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
