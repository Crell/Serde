<?php

declare(strict_types=1);

namespace Crell\Serde\Analyzer\Records;

use Crell\Serde\Analyzer\Attributes\Stuff;
use Crell\Serde\Analyzer\Attributes\Thing;

#[Stuff(a: 'hello')]
class Dummy
{
    public function __construct(
        #[Thing(5)]
        public readonly string $a = 'a',
        public readonly string $b = 'b',
    ) {}
}
