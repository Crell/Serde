<?php

namespace Crell\Serde\Records\FlatCollection;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;

class FlatThingList
{
    /**
     * @var FlatThing[]
     */
    #[SequenceField(arrayType: FlatThing::class)]
    #[Field(flatten: true)]
    public array $things;

    public function __construct(FlatThing ...$things){
        $this->things = $things;
    }
}
