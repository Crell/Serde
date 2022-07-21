<?php

declare(strict_types=1);

namespace Crell\Serde;

enum KeyType: string
{
    case String = 'string';
    case Int = 'int';
}
