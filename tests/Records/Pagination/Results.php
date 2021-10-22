<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Pagination;

use Crell\Serde\Field;

class Results
{
    public function __construct(
        #[Field(flatten: true)]
        public Pagination $pagination,
        #[Field(arrayType: Product::class)]
        public array $products,
    ) {}
}
