<?php

declare(strict_types=1);

namespace Crell\Serde;

class IntersectionTypesNotSupported extends \TypeError
{
    // @todo Use this in the error message.
    public readonly \ReflectionProperty $property;

    public static function create(\ReflectionProperty $property): static
    {
        $new = new self();
        $new->property = $property;
        return $new;
    }
}
