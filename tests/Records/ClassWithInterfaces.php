<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Records\InterfaceA;
use Crell\Serde\Records\InterfaceB;

class ClassWithInterfaces implements InterfaceA, InterfaceB
{
    public function __construct(
        public string $a,
    ) {}
}
