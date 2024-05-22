<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

#[TransitiveTypeField]
class Transitive
{
    public function __construct(
        public readonly string $name = 'Transitive',
    ) {
    }
}
