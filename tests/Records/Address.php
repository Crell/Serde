<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class Address
{
    public function __construct(
        public int $number,
        public string $street,
        public string $city,
        public string $state,
        public string $zip,
    ) {}
}
