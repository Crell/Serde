<?php

declare(strict_types=1);

namespace Crell\Serde\Records\RootMap;

use Crell\Serde\TypeMap;

#[TypeMap('type', [
    'a' => TypeA::class,
    'b' => TypeB::class,
])]
interface Type
{

}
