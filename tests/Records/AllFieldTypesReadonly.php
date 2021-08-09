<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

/**
 * A test class that includes all meaningful types of field, for testing purposes.
 */
class AllFieldTypesReadonly
{
    public function __construct(
        public readonly int $anint = 0,
        public readonly string $string = '',
        public readonly float $afloat = 0,
        public readonly bool $bool = true,
        public readonly ?\DateTimeImmutable $dateTimeImmutable = null,
        public readonly ?\DateTime $dateTime = null,
        public readonly array $simpleArray = [],
        public readonly array $assocArray = [],
        public readonly ?Point $simpleObject = null,
    ) {}
}
