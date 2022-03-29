<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;

class MultipleScopesDefaultTrue
{
    public function __construct(
        #[Field(scopes: ['one', 'two'])]
        public string $a = '',

        #[Field(scopes: ['one'])]
        #[Field(exclude: true, scopes: ['two'])]
        public string $b = '',

        #[Field(scopes: ['two'])]
        public string $c = '',

        #[Field(exclude: true, scopes: ['one', 'two'])]
        public string $d = '',

        public string $e = '',
    ) {}
}
