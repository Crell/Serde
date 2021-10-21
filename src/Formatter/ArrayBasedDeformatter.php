<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\Field;
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

        // We need to know the object properties to deserialize to, so
        // get the property list, taking a type map into account.
        // The key field is kept in the data so that the property writer
        // can also look up the right type.
        $class = $typeMap
            ? $typeMap->findClass($data[$typeMap->keyField()])
            : $field->phpType;

        $collectingField = null;
        $usedNames = [];

        // Index the properties by serialized name, not native name.
        foreach ($this->getAnalyzer()->analyze($class, ClassDef::class)->properties as $p) {
            $properties[$p->serializedName] = $p;
            if ($p->flatten) {
                $collectingField = $p;
            }
        }

        $ret = [];

        // First pull out the properties we know about.
        foreach ($properties as $p) {
            $usedNames[] = $p->serializedName;
            if ($p === $collectingField) {
                continue;
            }
            $ret[$p->serializedName] = ($p?->typeCategory->isEnum() || $p?->typeCategory->isCompound())
                ? $recursor($data, $p)
                : $data[$p->serializedName] ?? SerdeError::Missing;
        }

        // Any other values are for a collecting field, if any,
        // but may need further processing according to the collecting field.
        if ($collectingField && $collectingField->phpType === 'array' && $collectingField->typeMap) {
            $remaining = $this->getRemainingData($data, $usedNames);
            foreach ($remaining as $k => $v) {
                $class = $collectingField->typeMap->findClass($v[$collectingField->typeMap->keyField()]);
                $f = Field::create(serializedName: "$k", phpType: $class);
                $obj = $recursor($remaining, $f);
                $ret[$k] = $obj;
            }
        } else {
            $remaining = $this->getRemainingData($data, $usedNames);
            foreach ($remaining as $k => $v) {
                $ret[$k] = $v;
            }
        }

        return $ret;
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
