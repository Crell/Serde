<?php

declare(strict_types=1);

namespace Crell\Serde;

class CircularReferenceDetected extends \RuntimeException
{

    public static function create(object $object): self
    {
        $new = new self();
        $new->message = sprintf('Circular reference detected for object of class %s.  You cannot serialize an object tree with circular references of the same object.', $object::class);
        return $new;
    }
}
