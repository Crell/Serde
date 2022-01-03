<?php

declare(strict_types=1);

namespace Crell\Serde\Records\FlatMapNested;

use Crell\Serde\SequenceField;

class NestedA implements Nested
{
    public function __construct(
        public string $name,
        public Item $item,
        #[SequenceField(arrayType: Item::class)]
        public array $items,
    ) {
    }
}
