<?php

declare(strict_types=1);

namespace Crell\Serde\AST;

class StructValue implements Value
{
    public function __construct(
        public string $type,
        /** array<string, mixed> */
        public array $values = [],
    ) {}
}
