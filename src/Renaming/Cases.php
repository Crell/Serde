<?php

declare(strict_types=1);

namespace Crell\Serde\Renaming;

use function Crell\fp\afilter;
use function Crell\fp\amap;
use function Crell\fp\pipe;
use function Crell\fp\implode;

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
            self::snake_case => pipe($name,
                $this->splitString(...),
                amap(trim(...)),
                implode('_'),
                strtolower(...)
            ),
            // @todo The more interesting ones.
        };
    }

    protected function splitString(string $input): array
    {
        $input = str_replace('_', ' ', $input);

        return preg_split(
            '/(^[^A-Z]+|[A-Z][^A-Z]+)/',
            $input,
            -1, /* no limit for replacement count */
            PREG_SPLIT_NO_EMPTY /*don't return empty elements*/
            | PREG_SPLIT_DELIM_CAPTURE /*don't strip anything from output array*/
        );
    }
}
