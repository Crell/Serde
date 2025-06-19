<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\MixedField;

class MixedValObject
{
    public function __construct(
        #[MixedField(Point::class)]
        public mixed $val
    ) {}
}
