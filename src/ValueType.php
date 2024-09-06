<?php
declare(strict_types=1);

namespace Crell\Serde;

use function Crell\fp\all;
use function Crell\fp\amap;

enum ValueType
{
    case String;
    case Int;
    case Float;
    case Array;

    /**
     * @param array<mixed> $values
     */
    public function assert(array $values): bool
    {
        return match ($this) {
            self::String => all(is_string(...))($values),
            self::Int => all(is_int(...))($values),
            self::Float => all(is_float(...))($values),
            self::Array => all(is_array(...))($values),
        };
    }

    /**
     * @param array<mixed> $values
     * @return array<mixed>
     */
    public function coerce(array $values): array
    {
        return match ($this) {
            self::String => $values,
            self::Int => amap(intval(...))($values),
            self::Float => amap(floatval(...))($values),
            self::Array => $values,
        };
    }
}
