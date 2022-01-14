<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\ClassDef;
use Crell\Serde\Attributes\Field;

#[ClassDef]
class Flattening
{
    public function __construct(
        public string $first,
        public string $last,
        #[Field(flatten: true)]
        public array $other = [],
    ) {}
}
