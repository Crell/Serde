<?php

declare(strict_types=1);

namespace Crell\Serde\Records\RootMap;

use Crell\Serde\StaticTypeMap;

#[StaticTypeMap('type', [
    'a' => TypeA::class,
    'b' => TypeB::class,
])]
interface Type
{

}
