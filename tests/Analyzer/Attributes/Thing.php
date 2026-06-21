<?php

declare(strict_types=1);

namespace Crell\Serde\Analyzer\Attributes;

#[\Attribute]
class Thing
{
    public function __construct(
        public readonly int $a = 0,
        public readonly int $b = 0,
    ) {}
}
