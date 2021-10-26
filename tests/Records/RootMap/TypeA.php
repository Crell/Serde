<?php

declare(strict_types=1);

namespace Crell\Serde\Records\BadMap;

class TypeA implements Type
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
