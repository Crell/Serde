<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\KeyType;
use Crell\Serde\ValueType;

class ScalarArrays
{
    public function __construct(
        #[SequenceField(arrayType: ValueType::Int)]
        public array $ints,

        #[SequenceField(arrayType: ValueType::Float)]
        public array $floats,

        #[DictionaryField(keyType: KeyType::String, arrayType: ValueType::String)]
        public array $stringMap,

        #[DictionaryField(keyType: KeyType::String, arrayType: ValueType::Array)]
        public array $arrayMap,
    ) {}
}
