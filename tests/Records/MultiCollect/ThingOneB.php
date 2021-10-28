<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MultiCollect;

class ThingOneB implements GroupOne
{
    public function __construct(
        public string $third = '',
        public string $fourth = '',
    ) {
    }
}
