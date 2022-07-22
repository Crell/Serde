<?php

declare(strict_types=1);

namespace Crell\Serde\Renaming;

use function Crell\fp\afilter;
use function Crell\fp\amap;
use function Crell\fp\explode;
use function Crell\fp\flatten;
use function Crell\fp\implode;
use function Crell\fp\pipe;
use function Crell\fp\replace;

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
    case kebab_case;
    case CamelCase;
    case lowerCamelCase;

    public function convert(string $name): string
    {
        return match ($this) {
            self::Unchanged => $name,
            self::UPPERCASE => strtoupper($name),
            self::lowercase => strtolower($name),
            self::snake_case => pipe($name,
                $this->splitString(...),
                implode('_'),
                strtolower(...)
            ),
            self::kebab_case => pipe($name,
                $this->splitString(...),
                implode('-'),
                strtolower(...)
            ),
            self::CamelCase => pipe($name,
                $this->splitString(...),
                amap(ucfirst(...)),
                implode(''),
            ),
            self::lowerCamelCase => pipe($name,
                $this->splitString(...),
                amap(ucfirst(...)),
                implode(''),
                lcfirst(...),
            ),
        };
    }

    /**
     * @return string[]
     */
    protected function splitString(string $input): array
    {
        $words = preg_split(
            '/(^[^A-Z]+|[A-Z][^A-Z]+)/',
            $input,
            -1, /* no limit for replacement count */
            PREG_SPLIT_NO_EMPTY /* don't return empty elements */
            | PREG_SPLIT_DELIM_CAPTURE /* don't strip anything from output array */
        );

        return pipe($words,
            amap(replace('_', ' ')),
            amap(explode(' ')),
            flatten(...),
            amap(trim(...)),
            afilter(),
        );

    }
}
