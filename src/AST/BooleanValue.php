<?php

declare(strict_types=1);

namespace Crell\Serde\AST;

class BooleanValue implements PrimitiveValue
{
    public function __construct(
        public bool $value,
    ) {}
}
