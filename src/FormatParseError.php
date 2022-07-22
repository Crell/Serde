<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\Field;

class FormatParseError extends \RuntimeException
{
    public readonly Field $field;
    public readonly string $format;

    /**
     * The format-specific data fragment that is invalid.
     */
    public readonly mixed $decoded;

    public static function create(Field $field, string $format, mixed $decoded): self
    {
        $new = new self();

        $new->field = $field;
        $new->format = $format;
        $new->decoded = $decoded;

        $new->message = sprintf(
            'Error parsing %s input while trying to read field %s (%s).',
            $format, $field->phpName ?? $field->phpType, $field->serializedName);

        return $new;
    }
}
