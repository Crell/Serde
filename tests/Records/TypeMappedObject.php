<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class TypeMappedObject implements TypeMappedInterface
{
    public function __construct(
        public int $id = 1,
    ) {}
}
