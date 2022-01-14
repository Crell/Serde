<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Pagination;

use Crell\Serde\Attributes\Field;

class NestedPagination
{
    public function __construct(
        public int $total,
        public int $limit,
        #[Field(flatten: true)]
        public PaginationState $state,
    ) {}
}
