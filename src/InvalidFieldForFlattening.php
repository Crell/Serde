<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\Field;

class InvalidFieldForFlattening extends \InvalidArgumentException
{
    public readonly Field $field;

    public static function create(Field $field): self
    {
        $new = new self();

        $new->field = $field;

        $new->message = sprintf('Tried to flatten field %s of type %s.  Only objects and arrays may have the flatten key set.', $field->phpName ?? $field->serializedName, $field->phpType);

        return $new;
    }
}
