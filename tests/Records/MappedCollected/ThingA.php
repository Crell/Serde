<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MappedCollected;

class ThingA implements Thing
{
    public function __construct(
        public string $a,
        public string $b,
    ) {}
}
