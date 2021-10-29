<?php

declare(strict_types=1);

namespace Crell\Serde;

interface TypeField
{
    /**
     * Determines if this field is valid for a given type.
     */
    public function acceptsType(string $type): bool;
}
