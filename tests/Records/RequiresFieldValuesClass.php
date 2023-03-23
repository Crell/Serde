<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;

#[ClassSettings(requireValues: true)]
class RequiresFieldValuesClass
{
    public function __construct(
        public string $a,
        // This field has a default, so it being missing should not be an error.
        public string $b = 'B',
    ) {}
}
