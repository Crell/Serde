<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MultiCollect;

class ThingOneA implements GroupOne
{
    public function __construct(
        public string $first = '',
        public string $second = '',
    ) {
    }
}
