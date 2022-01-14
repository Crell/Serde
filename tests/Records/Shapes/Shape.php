<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Shapes;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\StaticTypeMap;

#[ClassSettings]
#[StaticTypeMap(key: 'shape', map: [
    'circle' => Circle::class,
    'rect' => Rectangle::class,
])]
interface Shape
{
    public function area(): float;
}
