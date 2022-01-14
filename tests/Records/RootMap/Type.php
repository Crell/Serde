<?php

declare(strict_types=1);

namespace Crell\Serde\Records\RootMap;

use Crell\Serde\Attributes\StaticTypeMap;

#[StaticTypeMap('type', [
    'a' => TypeA::class,
    'b' => TypeB::class,
])]
interface Type
{

}
