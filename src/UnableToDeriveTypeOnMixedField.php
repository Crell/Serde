<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Formatter\Deformatter;

class UnableToDeriveTypeOnMixedField extends \RuntimeException
{
    public readonly Deformatter $deformatter;
    public readonly Field $field;

    public static function create(Deformatter $deformatter, Field $field): self
    {
        $new = new self();
        $new->deformatter = $deformatter;
        $new->field = $field;

        $new->message = sprintf('The %s format does not support type introspection, and the %s (%s) field does not specify a type to deserialize to.', $deformatter->format(), $field->phpName, $field->serializedName);

        return $new;
    }
}
