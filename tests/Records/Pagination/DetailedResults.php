<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Pagination;

use Crell\Serde\Field;

class DetailedResults
{
    public function __construct(
        #[Field(flatten: true)]
        public NestedPagination $pagination,
        #[Field(flatten: true)]
        public ProductType $type,
        #[Field(arrayType: Product::class)]
        public array $products,
        #[Field(flatten: true)]
        public array $other = [],
    ) {}
}
