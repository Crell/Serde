<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\SequenceField;

class ImplodingArrays
{
    public function __construct(
        #[SequenceField(implodeOn: ', ', trim: true)]
        public array $seq = [],
        #[DictionaryField(implodeOn: ', ', joinOn: '=')]
        public array $dict = [],
    ) {}
}
