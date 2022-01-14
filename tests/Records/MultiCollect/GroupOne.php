<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MultiCollect;

use Crell\Serde\Attributes\StaticTypeMap;

#[StaticTypeMap(key: 'group_one', map: [
    'thing_a' => ThingOneA::class,
    'thing_b' => ThingOneB::class,
])]
interface GroupOne
{

}
