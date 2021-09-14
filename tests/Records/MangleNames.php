<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\ClassDef;
use Crell\Serde\Field;

#[ClassDef]
class MangleNames
{
    public function __construct(
        #[Field(name: 'renamed')]
        public string $customName = '',

        public string $toUpper = '',

    ) {}
}
