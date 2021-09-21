<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Shapes;

class Box
{
    public function __construct(
        public Shape $aShape,
    ) {}
}
