<?php

declare(strict_types=1);

namespace Crell\Serde\Renaming;

use Crell\Serde\Renaming\RenamingStrategy;

/**
 * Case fold property names in various ways.
 *
 * "Case" would be a more convenient and literate name for this enum,
 * but that's a reserved word.
 */
enum Cases implements RenamingStrategy
{
    case Unchanged;
    case UPPERCASE;
    case lowercase;
    case snake_case;
    case CamelCase;
    case lowerCamelCase;
    case kebab_case;

    public function convert(string $name): string
    {
        return match ($this) {
            self::Unchanged => $name,
            self::UPPERCASE => strtoupper($name),
            self::lowercase => strtolower($name),
            // @todo The more interesting ones.
        };
    }
}
