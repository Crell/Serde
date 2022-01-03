<?php

declare(strict_types=1);

namespace Crell\Serde;

class UnsupportedFormat extends \InvalidArgumentException
{
    public readonly string $format;

    public static function create(string $format, Direction $dir): self
    {
        $new = new self();
        $new->format = $format;

        $new->message = match ($dir) {
            Direction::Serialize => sprintf('No Formatter available for format %s.', $format),
            Direction::Deserialize => sprintf('No Deformatter available for format %s.', $format),
        };

        return $new;
    }
}
