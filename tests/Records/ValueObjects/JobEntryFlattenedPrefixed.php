<?php

namespace Crell\Serde\Records\ValueObjects;

use Crell\Serde\Attributes\Field;

class JobEntryFlattenedPrefixed
{
    public function __construct(
        #[Field(flatten: true, flattenPrefix: 'desc_')]
        public JobDescription $description,
    ) {}
}
