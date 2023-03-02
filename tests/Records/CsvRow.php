<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class CsvRow
{
    public function __construct(
        public string $name,
        public int $age,
        public float $balance,
    ) {}
}
