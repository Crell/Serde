<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class LiteralEnums
{
    public function __construct(
        public Size $size,
        public BackedSize $backedSize,
    ) {}
}
