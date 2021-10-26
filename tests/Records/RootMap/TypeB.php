<?php

declare(strict_types=1);

namespace Crell\Serde\Records\RootMap;

class TypeB implements Type
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
