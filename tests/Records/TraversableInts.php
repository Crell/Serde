<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Exception;
use Traversable;

class TraversableInts implements \IteratorAggregate
{
    public function __construct(
        public int $count,
    ) {}

    public function getIterator(): Traversable
    {
        return (function() {
            for ($i = 1; $i <= $this->count; ++$i) {
                yield $i;
            }
        })();
    }


}
