<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\UnionField;
use Crell\Serde\KeyType;

class UnionTypeSubTypeField
{
    public function __construct(
        #[UnionField('array', ['array' => new DictionaryField(Point::class, KeyType::String)])]
        public string|array $values,
    ) {}
}
