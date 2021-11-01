<?php

declare(strict_types=1);

namespace Crell\Serde\Records\FlatMapNested;

use Crell\Serde\ClassNameTypeMap;
use Crell\Serde\Field;
use Crell\Serde\SequenceField;

class HostObject
{
    public function __construct(
        #[Field(flatten: true)]
        public Nested $nested,
        #[SequenceField(arrayType: Item::class)]
        public array $list,
    ) {}
}

#[ClassNameTypeMap('type')]
interface Nested
{

}

class NestedA implements Nested
{
    public function __construct(
        public string $name,
        public Item $item,
        #[SequenceField(arrayType: Item::class)]
        public array $items,
    ) {}
}

class NestedB implements Nested
{
    public function __construct(
        public int $age,
        public Item $item,
        #[SequenceField(arrayType: Item::class)]
        public array $items,
    ) {}
}

class Item
{
    public function __construct(
        public int $a,
        public int $b,
    ) {}
}
