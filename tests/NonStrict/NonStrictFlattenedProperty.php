<?php

declare(strict_types=1);

namespace Crell\Serde\NonStrict;

use Crell\Serde\Attributes\Field;
use Crell\Serde\NonStrict;

class NonStrictFlattenedProperty
{
    public function __construct(
        #[Field(flatten: true)]
        public readonly NonStrict\NonStrictProperties $s,
    ) {
    }
}
