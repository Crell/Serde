<?php

declare(strict_types=1);

namespace Crell\Serde;

class IntersectionTypesNotSupported extends \TypeError
{
    public readonly \ReflectionProperty $property;

    public static function create(\ReflectionProperty $property): self
    {
        $new = new self();
        $new->property = $property;

        $new->message = sprintf('Serde does not currently support intersection type properties. The property %s has a union type, and thus cannot be serialized/deserialized.', $property->name);

        return $new;
    }
}
