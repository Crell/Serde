<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\Field;

class ObjectWithFlattenedNestedFlattenObject
{
    public function __construct(
        public string $description,
        #[Field(flatten: true)]
        public ?NestedFlattenObject $nested = null,
    ) {}
}
