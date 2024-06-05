<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class ClassWithPropertyWithTransitiveTypeField
{
    public function __construct(
        public readonly TransitiveField $transitive,
    ) {
    }
}
