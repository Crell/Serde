<?php

declare(strict_types=1);

namespace Crell\Serde;

enum ScalarType: string
{
    case String = 'string';

    case Int = 'int';

    case Float = 'float';

    case Bool = 'bool';
}
