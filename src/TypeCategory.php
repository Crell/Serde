<?php

declare(strict_types=1);

namespace Crell\Serde;

/**
 * The general cateogry of type.
 *
 * These do not correspond to actual types, but to the class of type that
 * Serde needs to handle differently.
 */
enum TypeCategory
{
    case Scalar;
    case Object;
    case Array;
    case UnitEnum;
    case IntEnum;
    case StringEnum;

    public function isEnum(): bool
    {
        return in_array($this, [self::UnitEnum, self::IntEnum, self::StringEnum], true);
    }

    public function isCompound(): bool
    {
        return in_array($this, [self::Object, self::Array], true);
    }
}
