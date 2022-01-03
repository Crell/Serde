<?php

declare(strict_types=1);

namespace Crell\Serde;

class FieldTypeIncompatible extends \InvalidArgumentException
{
    public readonly string $typeField;
    public readonly string $propertyType;

    public static function create(string $typeField, string $propertyType): self
    {
        $new = new self();
        $new->typeField = $typeField;
        $new->propertyType = $propertyType;

        $new->message = sprintf('Type field definition %s cannot be applied to a property of type %s.', $typeField, $propertyType);

        return $new;
    }
}
