<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Shapes;

use Crell\Serde\Attributes\ClassDef;
use Crell\Serde\Attributes\StaticTypeMap;

#[ClassDef]
#[StaticTypeMap(key: 'shape', map: [
    'circle' => Circle::class,
    'rect' => Rectangle::class,
])]
interface Shape
{
    public function area(): float;
}
