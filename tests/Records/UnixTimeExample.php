<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DateField;
use Crell\Serde\Attributes\UnixTimeField;
use DateTime;
use DateTimeImmutable;

class UnixTimeExample
{
    public function __construct(
        #[UnixTimeField]
        public DateTime $seconds,

        #[UnixTimeField(milliseconds: true)]
        public DateTimeImmutable $milliseconds,
    ) {}
}
