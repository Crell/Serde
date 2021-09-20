<?php

declare(strict_types=1);

namespace Crell\Serde\Renaming;

class Prefix implements RenamingStrategy
{
    public function __construct(readonly protected string $prefix) {}

    public function convert(string $name): string
    {
        return $this->prefix . $name;
    }
}
