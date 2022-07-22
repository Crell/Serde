<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\Field;

class TypeMapOnNonObjectField extends \InvalidArgumentException
{
    public readonly Field $field;

    public static function create(Field $field): self
    {
        $new = new self();

        $new->field = $field;

        $new->message = sprintf('Type maps may only be applied to object or array fields. Tried to apply type map to field %s of type %s.  Honestly I do not know how you even got here.', $field->phpName ?? $field->serializedName, $field->phpType);

        return $new;
    }
}
