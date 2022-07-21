<?php

declare(strict_types=1);

namespace Crell\Serde;

interface TypeField
{
    /**
     * Determines if this field is valid for a given type.
     */
    public function acceptsType(string $type): bool;

    /**
     * Validates that the provided value is legal according to this field definition.
     *
     * Basic type matching is already done by the Field, so there is no need to recheck
     * if it is an int or an array or something like that. Only deeper checks are needed.
     *
     * @param mixed $value
     * @return bool
     */
    public function validate(mixed $value): bool;
}
