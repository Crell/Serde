<?php

declare(strict_types=1);

namespace Crell\Serde\Records\ValueObjects;

use Crell\Serde\Attributes\Field;

class Email
{
    public function __construct(#[Field(serializedName: 'email')] public string $value) {}
}
