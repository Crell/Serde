<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\KeyType;

class DictionaryKeyTypes
{
    public function __construct(
        #[DictionaryField(arrayType: 'string', keyType: KeyType::String)]
        public array $stringKey = [],

        #[DictionaryField(arrayType: 'string', keyType: KeyType::Int)]
        public array $intKey = [],
    ) {}
}
