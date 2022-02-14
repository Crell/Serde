<?php

declare(strict_types=1);

namespace Crell\Serde\NonStrict;

use Crell\Serde\Attributes\Field;

class NonStrictProperties
{
    public function __construct(
        #[Field(strict: false)]
        public readonly int $int = 0,
        #[Field(strict: false)]
        public readonly float $float = 0,
        #[Field(strict: false)]
        public readonly string $string = '',
        #[Field(strict: false)]
        public readonly bool $bool = false,
    ) {
    }
}
