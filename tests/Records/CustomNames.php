<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\ClassDef;
use Crell\Serde\Field;

#[ClassDef]
class CustomNames
{
    public function __construct(
        #[Field(serializedName: 'firstName')]
        public string $first = '',
        #[Field(serializedName: 'lastName')]
        public string $last = '',
    ) {}
}
