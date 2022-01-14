<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Pagination;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;

class Results
{
    public function __construct(
        #[Field(flatten: true)]
        public Pagination $pagination,
        #[SequenceField(arrayType: Product::class)]
        public array $products,
    ) {}
}
