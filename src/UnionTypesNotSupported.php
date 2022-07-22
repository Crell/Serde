<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\Field;

class UnionTypesNotSupported extends \TypeError
{
    public readonly \ReflectionProperty $property;

    public static function create(\ReflectionProperty $property): self
    {
        $new = new self();
        $new->property = $property;

        $new->message = sprintf('Serde does not currently support union type properties. The property %s has a union type, and thus cannot be serialized/deserialized.', $property->name);

        return $new;
    }
}
