<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\SequenceField;

readonly final class NullablePointList
{
    /**
     * @param list<Point>|null $points
     */
    public function __construct(
        #[SequenceField(arrayType: Point::class)]
        public array|null $points = null,
    ) {
    }
}
