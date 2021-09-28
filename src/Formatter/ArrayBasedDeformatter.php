<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Field;
use Crell\Serde\SerdeError;

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
        return $decoded[$field->serializedName()];
    }

    public function deserializeFloat(mixed $decoded, Field $field): float|SerdeError
    {
        return $decoded[$field->serializedName()];
    }

    public function deserializeBool(mixed $decoded, Field $field): bool|SerdeError
    {
        return $decoded[$field->serializedName()];
    }

    public function deserializeString(mixed $decoded, Field $field): string|SerdeError
    {
        return $decoded[$field->serializedName()];
    }

    public function deserializeArray(mixed $decoded, Field $field, callable $recursor): array|SerdeError
    {
        return ($field->arrayType && class_exists($field->arrayType))
            ? array_map(static fn (mixed $value) => $recursor($value, $field->arrayType), $decoded[$field->serializedName()])
            : $decoded[$field->serializedName()];
    }

    public function deserializeDictionary(mixed $decoded, Field $field): array|SerdeError
    {
        return $decoded[$field->serializedName()] ?? SerdeError::Missing;
    }

    public function getRemainingData(mixed $source, array $used): array
    {
        return array_diff_key($source, array_flip($used));
    }
}
