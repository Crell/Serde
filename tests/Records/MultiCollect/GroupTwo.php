<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MultiCollect;

use Crell\Serde\StaticTypeMap;

#[StaticTypeMap(key: 'group_two', map: [
    'thing_c' => ThingTwoC::class,
    'thing_d' => ThingTwoD::class,
])]
interface GroupTwo
{

}
