<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\DateField;
use Crell\Serde\Attributes\Enums\UnixTimeResolution;
use Crell\Serde\Attributes\UnixTimeField;
use DateTime;
use DateTimeImmutable;

class UnixTimeExample
{
    public function __construct(
        #[UnixTimeField]
        public DateTime $seconds,

        #[UnixTimeField(resolution: UnixTimeResolution::Milliseconds)]
        public DateTimeImmutable $milliseconds,

        #[UnixTimeField(resolution: UnixTimeResolution::Microseconds)]
        public DateTimeImmutable $microseconds,
    ) {}
}
