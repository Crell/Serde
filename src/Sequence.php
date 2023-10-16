<?php

declare(strict_types=1);

namespace Crell\Serde;

/**
 * Represents a Sequence to the formatter.
 *
 * When passing a sequence to the formatter
 * to be written, we need additional type metadata
 * on each value in the sequence. This class is
 * a collection of those metadata entries.
 */
class Sequence
{
    /** @param CollectionItem[] $items */
    public function __construct(
        public iterable $items = [],
    ) {}

    public function add(CollectionItem $item): self
    {
        is_array($this->items)
            ? $this->items[] = $item
            : $this->items = (function () use ($item) {
                yield from $this->items;
                yield $item;
        })();

        return $this;
    }
}
