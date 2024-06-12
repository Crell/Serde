<?php

declare(strict_types = 1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\Field;

class OrderId
{
    public function __construct(
        #[Field(serializedName: 'id')]
        public readonly int $value,
    ) {}
}
