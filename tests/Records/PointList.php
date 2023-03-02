<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\SequenceField;

class PointList
{
    /**
     * @param Point[] $points
     */
    public function __construct(
        #[SequenceField(arrayType: Point::class)]
        public array $points,
    ) {}

}
