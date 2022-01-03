<?php

declare(strict_types=1);

namespace Crell\Serde\Records\FlatMapNested;

class Item
{
    public function __construct(
        public int $a,
        public int $b,
    ) {
    }
}
