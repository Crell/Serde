<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\SupportsScopes;
use Crell\Serde\CompoundType;
use Crell\Serde\TypeField;

/**
 * Specifies how a mixed field should be deserialized.
 *
 * In particular, it allows specifying a class type to which an array-ish
 * value should be deserialized.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class MixedField implements TypeField, SupportsScopes, CompoundType
{
    /**
     * @param string $suggestedType
     *   The class name that an array-ish value should be deserialized into.
     *   Primitive values will be left as is when deserializing.
     * @param array<string|null> $scopes
     *   The scopes in which this attribute should apply.
     */
    public function __construct(
        public readonly string $suggestedType,
        protected readonly array $scopes = [null],
    ) {}

    public function suggestedType(): string
    {
        return $this->suggestedType;
    }

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
