<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MappedCollected;

class ThingB implements Thing
{
    public function __construct(
        public string $c,
        public string $d,
    ) {}
}
