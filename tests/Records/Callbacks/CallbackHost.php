<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Callbacks;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\PostLoad;

class CallbackHost
{
    #[Field(exclude: true)]
    public readonly string $fullName;

    public function __construct(
        public readonly string $first,
        public readonly string $last,
    ) {
        $this->deriveFullName();
    }

    #[PostLoad]
    private function deriveFullName(): void
    {
        $this->fullName = "$this->first $this->last";
    }
}
