<?php

namespace Crell\Serde\Records\FlatCollection;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\KeyType;

class FlatThingMap
{
    /**
     * @var array<string, FlatThing>
     */
    #[DictionaryField(arrayType: FlatThing::class, keyType: KeyType::String)]
    #[Field(flatten: true)]
    public array $things;

    public function __construct(FlatThing ...$things){
        $this->things = $things;
    }
}
