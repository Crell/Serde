<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Pagination;

class Product
{
    public function __construct(
        public string $name,
        public float $price,
    ) {}
}
