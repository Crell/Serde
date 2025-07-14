<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\UnionField;
use Crell\Serde\Records\ValueObjects\Email;

class UnionTypes
{
    public function __construct(
        public string|int $stringInt,
        public int|float $intFloat,
        public Point|string $pointString,
        #[UnionField(Email::class)]
        public Point|Email|string $pointEmail,

    ) {}
}
