<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\Field;
use Crell\Serde\Serde;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeCategory;

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
                $ret[$k] = $recursor($decoded[$field->serializedName], $f->phpType, $f);
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
            $ret[$k] = $recursor($data, $f->phpType, $f);
        }

        return $ret;
    }

    /**
     *
     *
     * @param mixed $decoded
     * @param Field $field
     * @param callable $recursor
     * @param Field[] $properties
     * @return array|SerdeError
     */
    public function deserializeObject(mixed $decoded, Field $field, callable $recursor, array $properties): array|SerdeError
    {
        if (!isset($decoded[$field->serializedName])) {
            return SerdeError::Missing;
        }
        // @todo Still unsure if this should be an exception instead.
        if (!is_array($decoded[$field->serializedName])) {
            return SerdeError::FormatError;
        }

        $data = $decoded[$field->serializedName];

        if ($map = $field->typeMap) {
            $keyField = $map->keyField();
            $class = $map->findClass($data[$keyField]);
            $properties = $this->getAnalyzer()->analyze($class, ClassDef::class)->properties;
        }

        foreach ($properties as $p) {
            $lookup[$p->serializedName] = $p;
        }

        $ret = [];
        foreach ($data as $k => $v) {
            $f = $lookup[$k] ?? null;
            $key = $f?->serializedName ?? $k;
            $ret[$key] = in_array($f?->typeCategory, [TypeCategory::StringEnum, TypeCategory::UnitEnum, TypeCategory::IntEnum, TypeCategory::Object, TypeCategory::Array], strict: true)
                ? $recursor($data, $f->phpType, $f)
                : $v;
        }

        return $ret;
    }

    abstract protected function getAnalyzer(): ClassAnalyzer;

    public function getRemainingData(mixed $source, array $used): array
    {
        return array_diff_key($source, array_flip($used));
    }
}
