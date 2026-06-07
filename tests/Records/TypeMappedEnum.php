<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

enum TypeMappedEnum: int implements TypeMappedInterface
{
    case A = 1;
    case B = 2;
}
