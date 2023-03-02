<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\KeyType;

class Traversables
{
    public function __construct(
        public TraversableInts $lazyInts,
        public TraversablePoints $lazyPoints,
    ) {}
}
