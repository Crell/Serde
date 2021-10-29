<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\SequenceField;

class InvalidFieldType
{
    public function __construct(
        #[SequenceField]
        public int $a = 2,
    ) {}
}
