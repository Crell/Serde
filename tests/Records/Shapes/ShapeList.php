<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Shapes;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\SequenceField;

class ShapeList
{
    public function __construct(
        #[SequenceField(arrayType: Shape::class)]
        public array $shapeSeq = [],
        #[DictionaryField(arrayType: Shape::class)]
        public array $shapeDict = [],
    ) {}
}
