<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MultiCollect;

class ThingTwoD implements GroupTwo
{
    public function __construct(
        public string $seventh = '',
        public string $eighth = '',
    ) {
    }
}
