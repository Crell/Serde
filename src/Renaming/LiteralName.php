<?php

declare(strict_types=1);

namespace Crell\Serde\Renaming;

class LiteralName implements RenamingStrategy
{
    public function __construct(readonly protected string $name) {}

    public function convert(string $name): string
    {
        return $this->name;
    }
}
