<?php

declare(strict_types=1);

namespace Crell\Serde\AST;

class DictionaryValue implements Value
{
    public function __construct(
        public array $values = [],
    ) {}
}
