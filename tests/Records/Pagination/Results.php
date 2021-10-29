<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Pagination;

use Crell\Serde\Field;
use Crell\Serde\SequenceField;

class Results
{
    public function __construct(
        #[Field(flatten: true)]
        public Pagination $pagination,
        #[SequenceField(arrayType: Product::class)]
        public array $products,
    ) {}
}
