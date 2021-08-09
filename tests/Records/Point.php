<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

/**
 * The most basic test for a non-entity record.
 *
 * Saving one of these objects should always create a new record.
 * It also cannot be loaded by ID, obviously, but you can load it
 * directly from a query result.
 */

class Point
{
    public function __construct(
        public int $x,
        public int $y,
        public int $z,
    ) {}
}
