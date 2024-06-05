<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\ReducingTransitiveTypeField;

#[ReducingTransitiveTypeField]
class ReducibleClass
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
