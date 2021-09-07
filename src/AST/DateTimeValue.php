<?php

declare(strict_types=1);

namespace Crell\Serde\AST;

class DateTimeValue implements Value
{
    public function __construct(
        public string $dateTime,
        public string $dateTimeZone,
        public bool $immutable = true,
    ) {}
}
