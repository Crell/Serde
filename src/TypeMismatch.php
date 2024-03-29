<?php

declare(strict_types=1);

namespace Crell\Serde;

class TypeMismatch extends \InvalidArgumentException implements SerdeException
{

    public readonly string $name;
    public readonly string $expectedType;
    public readonly string $foundType;

    public static function create(string $name, string $expectedType, string $foundType): self
    {
        $new = new self();
        $new->name = $name;
        $new->expectedType = $expectedType;
        $new->foundType = $foundType;

        $new->message = sprintf('Expected value of type %s when writing to property %s, but found type %s.  Either your incoming data is invalid, or perhaps the field needs to be in non-strict mode.', $expectedType, $name, $foundType);

        return $new;
    }
}
