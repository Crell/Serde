<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\Serde\TypeField;

#[Attribute(Attribute::TARGET_CLASS)]
class TransitiveTypeField implements TypeField
{
    public function acceptsType(string $type): bool
    {
        return true;
    }

    public function validate(mixed $value): bool
    {
        return true;
    }
}
