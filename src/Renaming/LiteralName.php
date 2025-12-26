<?php

declare(strict_types=1);

namespace Crell\Serde\Renaming;

readonly class LiteralName implements RenamingStrategy
{
    public function __construct(protected string $name) {}

    public function convert(string $name): string
    {
        return $this->name;
    }
}
