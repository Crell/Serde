<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Pagination;

class ProductType
{
    public function __construct(
        public string $name = '',
        public string $category = '',
    ) {}
}
