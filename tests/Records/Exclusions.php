<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\Field;

class Exclusions
{
    public function __construct(
        public string $one,
        #[Field(exclude: true)]
        public string $two,
    ) {}
}
