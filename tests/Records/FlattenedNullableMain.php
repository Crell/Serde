<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\Field;

class FlattenedNullableMain
{
    public function __construct(
        #[Field(flatten: true)]
        public readonly ?FlattenedNullableNested $nested = null,
    ) {}
}
