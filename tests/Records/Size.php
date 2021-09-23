<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

enum Size
{
    case Small;
    case Medium;
    case Large;

    public const Huge = Size::Large;
}
