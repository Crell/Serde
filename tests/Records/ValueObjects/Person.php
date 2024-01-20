<?php

declare(strict_types=1);

namespace Crell\Serde\Records\ValueObjects;

use Crell\Serde\Attributes\Field;

class Person
{
    public function __construct(
        public string $name,
        #[Field(flatten: true)]
        public Age $age,
        #[Field(flatten: true)]
        public Email $email,
    ) {}
}
