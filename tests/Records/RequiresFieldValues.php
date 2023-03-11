<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;

#[ClassSettings]
class RequiresFieldValues
{
    public function __construct(
        #[Field(requireValue: true)]
        public string $a,
        // This field has a default, so it being missing should not be an error.
        #[Field(requireValue: true)]
        public string $b = 'B',
    ) {}
}
