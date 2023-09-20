<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class FlattenedNullableNested
{
    public function __construct(
        public readonly ?string $firstName = null,
    ) {}
}
