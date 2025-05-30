<?php

namespace Crell\Serde\Attributes;

use Crell\AttributeUtils\SupportsScopes;
use Crell\Serde\TypeField;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class UInt64 implements TypeField, SupportsScopes
{
    /**
     * @param array<string|null> $scopes
     */
    public function __construct(protected readonly array $scopes = [null]) {}

    public function acceptsType(string $type): bool
    {
        return $type === 'string' || $type === 'int';
    }

    public function validate(mixed $value): bool
    {
        return is_numeric($value);
    }

    public function scopes(): array
    {
        return $this->scopes;
    }
}
