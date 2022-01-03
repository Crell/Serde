<?php

declare(strict_types=1);

namespace Crell\Serde;

class NoTypeMapDefinedForKey extends \InvalidArgumentException
{
    public readonly string $key;
    public readonly string $fieldName;

    public static function create(string $key, string $fieldName): self
    {
        $new = new self();
        $new->key = $key;
        $new->fieldName = $fieldName;

        $new->message = sprintf('No matching class found for key "%s" when deserializing to "%s".', $key, $fieldName);

        return $new;
    }
}
