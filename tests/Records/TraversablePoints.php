<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Exception;
use Traversable;

class TraversablePoints implements \IteratorAggregate
{
    public function __construct(
        public int $count,
        public Point $point,
    ) {}

    public function getIterator(): Traversable
    {
        return (function() {
            for ($i = 0; $i < $this->count; ++$i) {
                yield new Point(
                    $this->point->x + $i,
                    $this->point->y + $i,
                    $this->point->z + $i,
                );
            }
        })();
    }


}
