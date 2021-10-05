<?php

declare(strict_types=1);

namespace Crell\Serde;

class CollectionItem
{
    public function __construct(
        public readonly Field $field,
        public readonly mixed $value,
    ) {
    }
}
