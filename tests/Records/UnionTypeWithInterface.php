<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\StaticTypeMap;

class UnionTypeWithInterface
{
    public function __construct(
        public int|StandardTest $value,
    ) {}
}

#[StaticTypeMap('type', [
    'act' => ACT::class,
    'sat' => SAT::class,
])]
interface StandardTest {}

class ACT implements StandardTest
{
    public function __construct(
        public int $score,
    ) {}
}

class SAT implements StandardTest
{
    public function __construct(
        public int $score,
    ) {}
}
