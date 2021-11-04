<?php

declare(strict_types=1);

namespace Crell\Serde;

class NoWriterFound extends \RuntimeException
{
    public readonly string $type;

    public readonly string $format;

    public static function create(string $type, string $format): static
    {
        $new = new self();
        $new->type = $type;
        $new->format = $format;

        $new->message = sprintf('No Property Writer is available that can process %s types for format %s.', $type, $format);
        return $new;
    }
}
