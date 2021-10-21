<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MappedCollected;

use Crell\Serde\ClassNameTypeMap;
use Crell\Serde\Field;

class ThingList
{
    public function __construct(
        public string $name,
        #[Field(flatten: true)]
        #[ClassNameTypeMap(key: 'class')]
        public array $things = [],
    ) {}
}
