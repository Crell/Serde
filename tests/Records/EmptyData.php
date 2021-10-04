<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class EmptyData
{
    public ?string $nullableUninitialized;

    public string $uninitialized;

    public string $nonConstructorDefault = 'narf';

    public readonly string $roEmpty;

    public function __construct(
        public string $required,
        public string $withDefault = 'boop',
        public ?string $nullable = null,
        public readonly ?string $roNullable = null,
    ) {}
}
