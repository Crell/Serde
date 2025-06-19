<?php

namespace Crell\Serde\Records\FlatCollection;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\KeyType;
use Crell\Serde\Records\Point;

class FlatThingMap
{
    /**
     * @var array<string, Point>
     */
    #[DictionaryField(arrayType: Point::class, keyType: KeyType::String)]
    #[Field(flatten: true)]
    public array $things;

    public function __construct(Point ...$things){
        $this->things = $things;
    }
}
