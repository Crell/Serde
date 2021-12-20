<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Field;

class AliasedFields
{
    public function __construct(
        #[Field(alias: ['un'])]
        public int $one = 1,
        #[Field(alias: ['deux', 'dos'])]
        public string $two = 'two',
        #[Field(alias: ['dot'])]
        public ?Point $point = null,
    ) {}
}
