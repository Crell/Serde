<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class NullableTypesObject
{
    public function __construct(
        public ?string $str,
        public ?int $int,
        public ?float $float,
        public ?bool $bool,
        public ?array $arr,
    ) {}
}
