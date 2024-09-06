<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\KeyType;
use Crell\Serde\ValueType;

class WeakLists
{
    public function __construct(
        #[Field(strict: false), SequenceField(ValueType::Int)]
        public array $seq,
        #[Field(strict: false), DictionaryField(arrayType: ValueType::Int, keyType: KeyType::String)]
        public array $dict,
    ) {}
}
