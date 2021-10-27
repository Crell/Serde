<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Pagination;

class PaginationState
{
    public function __construct(
        public int $offset,
    ) {
    }
}
