<?php

declare(strict_types=1);

namespace Crell\Serde;

interface TypeField
{
    public function acceptsType(string $type): bool;
}
