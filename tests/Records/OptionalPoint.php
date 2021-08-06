<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

/**
 * A basic test with optional fields, with default values.
 */
class OptionalPoint
{
    public function __construct(
        public int $x = 0,
        public int $y = 0,
        public int $z = 0,
    ) {}
}
