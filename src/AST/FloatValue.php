<?php

declare(strict_types=1);

namespace Crell\Serde\AST;

class FloatValue implements PrimitiveValue
{
    public function __construct(
        public float $value,
    ) {}
}
