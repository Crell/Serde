<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

class FormatterStream
{
    public bool $root = true;

    public function __construct(
        public mixed $stream,
    ) {}

    public static function new(...$args): static
    {
        return new static(...$args);
    }
}
