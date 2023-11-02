<?php

declare(strict_types=1);

namespace Crell\Serde;

class MissingRequiredValueWhenDeserializing extends \InvalidArgumentException implements SerdeException
{
    public readonly string $name;
    public readonly string $class;
    public readonly string $format;

    public static function create(string $name, string $class, string $format): self
    {
        $new = new self();
        $new->name = $name;
        $new->class = $class;
        $new->format = $format;

        $new->message = sprintf('No data found for required field "%s" on class %s when deserializing from %s.', $name, $class, $format);

        return $new;
    }
}
