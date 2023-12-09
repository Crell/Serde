<?php

declare(strict_types=1);

namespace Crell\Serde\Records\ValueObjects;

use Crell\Serde\Attributes\Field;

class Age
{
    public function __construct(#[Field(serializedName: 'age')] public int $value)
    {
        if ($this->value < 0) {
            throw new \InvalidArgumentException('Age cannot be negative.');
        }
    }
}
