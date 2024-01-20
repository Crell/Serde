<?php

namespace Crell\Serde\Records\ValueObjects;

use Crell\Serde\Attributes\Field;

class JobEntryFlattened
{
    public function __construct(
        #[Field(flatten: true)]
        public JobDescription $description,
    ) {}
}
