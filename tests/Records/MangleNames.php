<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Cases;
use Crell\Serde\ClassDef;
use Crell\Serde\Field;

#[ClassDef]
class MangleNames
{
    public function __construct(
        #[Field(name: 'renamed')]
        public string $customName = '',
        #[Field(caseFold: Cases::UPPERCASE)]
        public string $toUpper = '',
        #[Field(caseFold: Cases::lowercase)]
        public string $toLower = '',
    ) {}
}
