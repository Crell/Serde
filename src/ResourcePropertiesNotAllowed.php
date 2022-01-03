<?php

declare(strict_types=1);

namespace Crell\Serde;

class ResourcePropertiesNotAllowed extends \InvalidArgumentException
{
    public readonly string $name;

    public static function create(string $name): self
    {
        $new = new self();
        $new->name = $name;

        $new->message = sprintf('Resource properties cannot be serialized.  Please exclude %s.', $name);

        return $new;
    }
}
