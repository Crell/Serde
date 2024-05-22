<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class TransitiveExample
{
    public function __construct(
        public readonly Transitive $transitive,
    ) {
    }
}
