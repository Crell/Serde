<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\ScalarType;

class ScalarArraysObject
{
    public function __construct(
        #[SequenceField(arrayType: ScalarType::Int)]
        public array $ints = [],
        #[SequenceField(arrayType: ScalarType::String)]
        public array $strings = [],
        #[SequenceField(arrayType: ScalarType::Float)]
        public array $floats = [],
        #[SequenceField(arrayType: ScalarType::Bool)]
        public array $bools = [],
    ) {
    }
}
