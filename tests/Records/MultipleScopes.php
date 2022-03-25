<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;

#[ClassSettings(scopes: ['one', 'two'], includeFieldsByDefault: false)]
class MultipleScopes
{
    public function __construct(
        #[Field(scopes: ['one', 'two'])]
        public string $a = '',

        #[Field(scopes: ['one'])]
        public string $b = '',

        #[Field(scopes: ['two'])]
        public string $c = '',

        public string $d = '',

        public string $e = '',
    ) {}
}
