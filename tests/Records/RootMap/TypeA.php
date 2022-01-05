<?php

declare(strict_types=1);

namespace Crell\Serde\Records\RootMap;

class TypeA implements Type
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
