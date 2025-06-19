<?php

namespace Crell\Serde\Records\FlatCollection;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\Records\Point;

class FlatThingList
{
    /**
     * @var Point[]
     */
    #[SequenceField(arrayType: Point::class)]
    #[Field(flatten: true)]
    public array $things;

    public function __construct(Point ...$things){
        $this->things = $things;
    }
}
