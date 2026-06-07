<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\StaticTypeMap;

#[StaticTypeMap(key: 'type', map: [
    'enum' => TypeMappedEnum::class,
    'object' => TypeMappedObject::class,
])]
interface TypeMappedInterface
{
}
