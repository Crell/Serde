<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

enum BackedSize: string
{
    case Small = 'S';
    case Medium = 'M';
    case Large = 'L';

    public const Huge = Size::Large;
}
