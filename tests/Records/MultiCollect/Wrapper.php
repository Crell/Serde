<?php

declare(strict_types=1);

namespace Crell\Serde\Records\MultiCollect;

use Crell\Serde\Field;

class Wrapper
{
    public function __construct(
        #[Field(flatten: true)]
        public GroupOne $one,
        #[Field(flatten: true)]
        public GroupTwo $two,
        #[Field(flatten: true)]
        public array $other = [],
    ) {}
}
