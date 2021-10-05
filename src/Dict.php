<?php

declare(strict_types=1);

namespace Crell\Serde;

/**
 * Represents a dictionary to the formatter.
 *
 * When passing a dictionary to the formatter
 * to be written, we need additional type metadata
 * on each value in the dictionary. This class is
 * a collection of those metadata entries.
 */
class Dict
{
    /** @var CollectionItem[] */
    public array $items = [];
}
