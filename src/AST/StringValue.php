<?php

declare(strict_types=1);

namespace Crell\Serde\AST;

class StringValue implements PrimitiveValue
{
    public function __construct(
        public string $value,
    ) {}
}
