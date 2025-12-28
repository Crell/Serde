<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use DateTimeInterface;

class DateTimeInterfaceExample {
    public function __construct(
        public DateTimeInterface $interfaceProperty,
    ) {}
}
