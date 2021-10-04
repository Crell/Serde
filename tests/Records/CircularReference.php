<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class CircularReference
{
    public CircularReference $ref;

    public function __construct(
        public string $name,
    ) {}
}
