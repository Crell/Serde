<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\Field;

class ExplicitDefaults
{
    public function __construct(
        #[Field(default: "42")]
        public string $bar,
        #[Field(default: null)]
        public ?string $name,
    ) {}
}
