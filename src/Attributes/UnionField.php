<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Crell\AttributeUtils\FromReflectionProperty;
use Crell\AttributeUtils\SupportsScopes;
use Crell\AttributeUtils\TypeDef;
use Crell\Serde\CompoundType;
use Crell\Serde\TypeField;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class UnionField implements TypeField, SupportsScopes, FromReflectionProperty, CompoundType
{
    // @todo This may make more sense on Field. Not sure yet.
    public readonly TypeDef $typeDef;

    /**
     * @param string $primaryType
     * @param array<string|null> $scopes
     *   The scopes in which this attribute should apply.
     *
     * @todo Maybe we need to allow passing manual TypeFields as an array to this attribute,
     *   so as to allow things like array or datetime in the union?
    */
    public function __construct(
        public readonly string $primaryType,
        protected readonly array $scopes = [null],
    ) {}

    public function fromReflection(\ReflectionProperty $subject): void
    {
        $this->typeDef ??= new TypeDef($subject->getType());
    }

    public function primaryType(): string
    {
        return $this->primaryType;
    }

    public function scopes(): array
    {
        return $this->scopes;
    }

    public function acceptsType(string $type): bool
    {
        return $this->typeDef->accepts($type);
    }

    public function validate(mixed $value): bool
    {
        // @todo We can probably do better.
        return true;
    }
}
