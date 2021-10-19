<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class NestedObject
{
    public function __construct(
        public string $name,
        public ?NestedObject $child = null,
    ) {}
}
