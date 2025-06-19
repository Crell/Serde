<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Crell\AttributeUtils\SupportsScopes;
use Crell\Serde\TypeField;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MixedField implements TypeField, SupportsScopes
{
    public function __construct(
        public readonly string $suggestedType,
        protected readonly array $scopes = [null],
    ) {}

    public function scopes(): array
    {
        return $this->scopes;
    }

    public function acceptsType(string $type): bool
    {
        return $type === 'mixed';
    }

    public function validate(mixed $value): bool
    {
        return true;
    }
}
