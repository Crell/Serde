<?php

declare(strict_types=1);

namespace Crell\Serde;

enum Cases
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
