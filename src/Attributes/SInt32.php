<?php

namespace Crell\Serde\Attributes;

use Crell\AttributeUtils\SupportsScopes;
use Crell\Serde\TypeField;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class SInt32 implements TypeField, SupportsScopes
{

    /**
     * @param array<string|null> $scopes
     */
    public function __construct(protected readonly array $scopes = [null]) {}

    public function scopes(): array
    {
        return $this->scopes;
    }

    public function acceptsType(string $type): bool
    {
        return $type === 'int';
    }

    public function validate(mixed $value): bool
    {
        return is_numeric($value);
    }
}
