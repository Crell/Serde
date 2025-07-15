<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class ACT implements StandardizedTest
{
    public function __construct(
        public int $score,
    ) {}
}
