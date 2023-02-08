<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DateField;
use DateTime;
use DateTimeImmutable;

class DateTimeExample
{
    public function __construct(
        public DateTime $default,
        public DateTimeImmutable $immutableDefault,

        #[DateField(format: 'Y-m-d')]
        public DateTime $ymd,

        #[DateField(format: 'Y-m-d')]
        public DateTimeImmutable $immutableYmd,

        #[DateField(timezone: 'UTC')]
        public DateTime $forceToUtc,

        #[DateField(timezone: 'America/Chicago')]
        public DateTimeImmutable $forceToChicago,
    ) {}
}
