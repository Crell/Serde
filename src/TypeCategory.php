<?php

declare(strict_types=1);

namespace Crell\Serde;

// @todo I dislike this name.
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
}
