<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DateField;
use Crell\Serde\Attributes\UnixTime;
use DateTime;
use DateTimeImmutable;

class UnixTimeExample
{
    public function __construct(
        #[UnixTime]
        public DateTime $seconds,

        #[UnixTime(milliseconds: true)]
        public DateTimeImmutable $milliseconds,
    ) {}
}
