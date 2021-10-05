<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Field;

class NestedFlattenObject
{
    public function __construct(
        public string $name,
        #[Field(flatten: true)]
        public array $other,
        public ?NestedFlattenObject $child = null,
    ) {}
}
