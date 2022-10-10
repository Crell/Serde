<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class NullArrays
{
    public function __construct(
        public array $arr = [null],
    ) {}
}
