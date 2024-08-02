<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class NullProps
{
    public function __construct(
        public ?int $int = null,
        public ?float $float = null,
        public ?string $string = null,
        public ?array $array = null,
        public ?NullProps $object = null,
        public ?BackedSize $enum = null,
    ) {}
}
