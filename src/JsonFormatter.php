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

    public function serializeInt(mixed $runningValue, string $name, int $next): mixed
    {
        $runningValue[$name] = $next;
        return $runningValue;
    }

    public function serializeFloat(mixed $runningValue, string $name, float $next): mixed
    {
        $runningValue[$name] = $next;
        return $runningValue;
    }

    public function serializeString(mixed $runningValue, string $name, string $next): mixed
    {
        $runningValue[$name] = $next;
        return $runningValue;
    }

    public function serializeBool(mixed $runningValue, string $name, bool $next): mixed
    {
        $runningValue[$name] = $next;
        return $runningValue;
    }

    public function serializeArray(mixed $runningValue, string $name, array $next): mixed
    {
        foreach ($next as $k => $v) {
            $runningValue[$name][$k] = $v;
        }
        return $runningValue;
    }

    public function serializeObject(mixed $runningValue, string $name, object $next, callable $recursor): mixed
    {
        $runningValue[$name] = $recursor($next, []);
        return $runningValue;
    }

    public function deserializeInitialize(string $serialized): mixed
    {
        return json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
    }

    public function deserializeInt(mixed $decoded, string $name): int|SerdeError
    {
        return $decoded[$name];
    }

    public function deserializeFloat(mixed $decoded, string $name): float|SerdeError
    {
        return $decoded[$name];
    }

    public function deserializeBool(mixed $decoded, string $name): bool|SerdeError
    {
        return $decoded[$name];
    }

    public function deserializeString(mixed $decoded, string $name): string|SerdeError
    {
        return $decoded[$name];
    }

    public function deserializeArray(mixed $decoded, string $name): array|SerdeError
    {
        return $decoded[$name] ?? SerdeError::Missing;
    }

    public function deserializeObject(mixed $decoded, string $name, callable $recursor, string $targetType): object
    {
        $valueToDeserialize = $decoded[$name];
        return $recursor($valueToDeserialize, $targetType);
    }

    public function getRemainingData(mixed $source, array $used): mixed
    {
        return array_diff_key($source, array_flip($used));
    }

    public function finalizeDeserialize(mixed $decoded): void
    {

    }
}
