<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\KeyType;

class Iterables
{
    public function __construct(
        #[SequenceField]
        public iterable $lazyInts,
        #[DictionaryField(keyType: KeyType::String)]
        public iterable $lazyIntDict,
        #[SequenceField(arrayType: Point::class)]
        public iterable $lazyPoints,
        #[DictionaryField(arrayType: Point::class, keyType: KeyType::String)]
        public iterable $lazyPointsDict,
    ) {}
}
