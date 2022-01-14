<?php

declare(strict_types=1);

namespace Crell\Serde\Records\FlatMapNested;

use Crell\Serde\Attributes\SequenceField;

class NestedB implements Nested
{
    public function __construct(
        public int $age,
        public Item $item,
        #[SequenceField(arrayType: Item::class)]
        public array $items,
    ) {
    }
}
