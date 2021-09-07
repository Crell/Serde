<?php

declare(strict_types=1);

namespace Crell\Serde\AST;

class SequenceValue implements Value
{
    public function __construct(
        /** Value[] */
        public array $values = [],
        public ?string $type = null,
    ) {}
}
