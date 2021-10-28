<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MultiCollect;

class ThingTwoC implements GroupTwo
{
    public function __construct(
        public string $fifth = '',
        public string $sixth = '',
    ) {
    }
}
