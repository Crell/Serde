<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\KeyType;

class NullableDictionaryObject
{
    public function __construct(
        #[DictionaryField(keyType: KeyType::Int)]
        public null|array $arr = null,
    ) {}
}
