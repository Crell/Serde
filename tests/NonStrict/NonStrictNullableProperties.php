<?php

declare(strict_types=1);

namespace Crell\Serde\NonStrict;

use Crell\Serde\Attributes\Field;

class NonStrictNullableProperties
{
    public function __construct(
        #[Field(strict: false)]
        public readonly ?int $int = null,
        #[Field(strict: false)]
        public readonly ?float $float = null,
        #[Field(strict: false)]
        public readonly ?string $string = null,
        #[Field(strict: false)]
        public readonly ?bool $bool = null,
    ) {
    }
}
