<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\Serde\TypeField;

#[Attribute(Attribute::TARGET_CLASS)]
class ReducingTransitiveTypeField implements TypeField
{
    public function acceptsType(string $type): bool
    {
        return $type === 'object' || class_exists($type) || interface_exists($type);
    }

    public function validate(mixed $value): bool
    {
        return true;
    }
}
