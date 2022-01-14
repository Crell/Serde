<?php

declare(strict_types=1);

namespace Crell\Serde\Records\FlatMapNested;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;

class HostObject
{
    public function __construct(
        #[Field(flatten: true)]
        public Nested $nested,
        #[SequenceField(arrayType: Item::class)]
        public array $list,
    ) {}
}
