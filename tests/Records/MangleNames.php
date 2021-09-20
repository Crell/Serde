<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Renaming\Cases;
use Crell\Serde\ClassDef;
use Crell\Serde\Field;
use Crell\Serde\Renaming\Prefix;

#[ClassDef]
class MangleNames
{
    public function __construct(
        #[Field(serializedName: 'renamed')]
        public string $customName = '',
        #[Field(renamingStrategy: Cases::UPPERCASE)]
        public string $toUpper = '',
        #[Field(renamingStrategy: Cases::lowercase)]
        public string $toLower = '',
        #[Field(renamingStrategy: new Prefix('beep_'))]
        public string $prefix = '',
    ) {}
}
