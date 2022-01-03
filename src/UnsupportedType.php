<?php

declare(strict_types=1);

namespace Crell\Serde;

class UnsupportedType extends \RuntimeException
{
    public readonly string $type;

    public static function create(string $type): self
    {
        $new = new self();
        $new->type = $type;

        $new->message = sprintf('No type %s found. Cannot deserialize to a type that does not exist.', $type);

        return $new;
    }
}
