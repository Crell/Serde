<?php

declare(strict_types=1);

namespace Crell\Serde;

class JsonFormatter
{
    public function initialize(): mixed
    {
        return [];
    }

    public function finalize(mixed $val): string
    {
        return json_encode($val, JSON_THROW_ON_ERROR);
    }

    public function serializeInt(mixed $val, string $name, int $next): mixed
    {
        $val[$name] = $next;
        return $val;
    }

    public function serializeFloat(mixed $val, string $name, float $next): mixed
    {
        $val[$name] = $next;
        return $val;
    }

    public function serializeString(mixed $val, string $name, string $next): mixed
    {
        $val[$name] = $next;
        return $val;
    }

    public function serializeBool(mixed $val, string $name, bool $next): mixed
    {
        $val[$name] = $next;
        return $val;
    }

    public function serializeArray(mixed $val, string $name, array $next): mixed
    {
        foreach ($next as $k => $v) {
            $val[$name][$k] = $v;
        }
        return $val;
    }

    public function serializeObject(mixed $val, string $name, object $next): mixed
    {
        $val[$name] = $next;
        return $val;
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

    public function deserializeObject(mixed $decoded, string $name): object
    {
        return $decoded[$name];
    }
}
