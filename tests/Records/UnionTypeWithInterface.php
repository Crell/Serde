<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\StaticTypeMap;

class UnionTypeWithInterface
{
    public function __construct(
        public int|StandardizedTest $value,
    ) {}
}

#[StaticTypeMap('type', [
    'act' => ACT::class,
    'sat' => SAT::class,
])]
interface StandardizedTest {}
