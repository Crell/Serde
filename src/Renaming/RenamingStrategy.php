<?php

declare(strict_types=1);

namespace Crell\Serde\Renaming;

interface RenamingStrategy
{
    public function convert(string $name): string;
}
