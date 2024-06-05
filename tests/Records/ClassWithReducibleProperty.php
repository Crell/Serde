<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class ClassWithReducibleProperty
{
    public function __construct(
        public ReducibleClass $dbReference,
    ) {}
}
