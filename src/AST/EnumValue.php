<?php

declare(strict_types=1);

namespace Crell\Serde\AST;

class EnumValue implements Value
{
    public function __construct(
        public string $type,
        public string|int $value,
    ) {}
}
