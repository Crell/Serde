<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MappedCollected;

class ThingC implements Thing
{
    public function __construct(
        public string $e,
        public string $f,
    ) {}
}
