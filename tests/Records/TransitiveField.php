<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\TransitiveTypeField;

#[TransitiveTypeField]
class TransitiveField
{
    public function __construct(
        public readonly string $name = 'Transitive',
    ) {
    }
}
