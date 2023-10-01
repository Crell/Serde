<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;

class SequenceOfStrings
{
    public function __construct(
        #[SequenceField]
        public array $strict,

        #[Field(strict: false)]
        #[SequenceField]
        public array $nonstrict,
    ) {}
}
