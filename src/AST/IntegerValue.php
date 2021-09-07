<?php

declare(strict_types=1);

namespace Crell\Serde\AST;

class IntegerValue implements PrimitiveValue
{
    public function __construct(
        public int $value,
    ) {}
}
