<?php

declare(strict_types=1);

namespace Crell\Serde;

/**
 * A compound type is a type that may carry more than one sub-type, like a Union type.
 */
interface CompoundType
{
    /**
     * Returns the primary type for this compound type.
     *
     * Because most of Serde assumes a single type, any compound type
     * needs to declare what single type it falls back to in most cases.
     */
    public function suggestedType(): string;
}
