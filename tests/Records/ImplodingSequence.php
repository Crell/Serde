<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\SequenceField;

class ImplodingSequence
{
    public function __construct(
        #[SequenceField(implodeOn: ', ', trim: true)]
        public array $values = [],
    ) {}
}
