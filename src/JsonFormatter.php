<?php

declare(strict_types=1);

namespace Crell\Serde;

class JsonFormatter
{
    public function initialize(): mixed
    {
        return [];
    }

    public function finalize(mixed $runningValue): string
    {
        return json_encode($runningValue, JSON_THROW_ON_ERROR);
    }

    public function serializeInt(mixed $runningValue, Field $field, int $next): mixed
    {
        $runningValue[$field->serializedName()] = $next;
        return $runningValue;
    }

    public function serializeFloat(mixed $runningValue, Field $field, float $next): mixed
    {
        $runningValue[$field->serializedName()] = $next;
        return $runningValue;
    }

    public function serializeString(mixed $runningValue, Field $field, string $next): mixed
    {
        $runningValue[$field->serializedName()] = $next;
        return $runningValue;
    }

    public function serializeBool(mixed $runningValue, Field $field, bool $next): mixed
    {
        $runningValue[$field->serializedName()] = $next;
        return $runningValue;
    }

    public function serializeArray(mixed $runningValue, Field $field, array $next, callable $recursor): mixed
    {
        $name = $field->serializedName();
        foreach ($next as $k => $v) {
            $runningValue[$name][$k] = is_object($v) ? $recursor($v, []) : $v;
        }
        return $runningValue;
    }

    public function serializeDictionary(mixed $runningValue, Field $field, array $next, callable $recursor): mixed
    {
        $name = $field->serializedName();
        foreach ($next as $k => $v) {
            $runningValue[$name][$k] = match (true) {
                is_object($v) => $recursor($v, []),
                is_array($v) => $recursor($v, []),
                default => $v,
            };
        }
        return $runningValue;
    }

    public function deserializeInitialize(string $serialized): mixed
    {
        return json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
    }

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

    public function getRemainingData(mixed $source, array $used): mixed
    {
        return array_diff_key($source, array_flip($used));
    }

    public function finalizeDeserialize(mixed $decoded): void
    {

    }
}
