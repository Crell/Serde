<?php

namespace Crell\Serde\Records\ValueObjects;

use Crell\Serde\Attributes\Field;

class JobDescription
{
    public function __construct(
        #[Field(flatten: true, flattenPrefix: 'min_')]
        public Age $minAge,
        #[Field(flatten: true, flattenPrefix: 'max_')]
        public Age $maxAge,
    ) {}
}
