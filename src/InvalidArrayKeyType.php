<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;

class InvalidArrayKeyType extends \InvalidArgumentException
{
    public readonly Field $field;
    public readonly string $foundType;

    public static function create(Field $field, string $foundType): self
    {
        $new = new self();
        $new->field = $field;
        $new->foundType = $foundType;

        /** @var DictionaryField  */
        $typeField = $field->typeField;

        $new->message = sprintf('Property %s is marked as needing an array key of type %s, but %s found.', $field->phpName, $typeField->keyType?->value, $foundType);

        return $new;
    }
}
