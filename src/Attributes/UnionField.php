<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Crell\AttributeUtils\FromReflectionProperty;
use Crell\AttributeUtils\TypeDef;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class UnionField extends MixedField implements FromReflectionProperty
{
    // @todo This is also stored on Field; it would be nice to read from there,
    //   but TypeFields don't get a reference back to the Field.  That may be
    //   something to improve in 2.0.
    public readonly TypeDef $typeDef;

    /**
     * @param string $primaryType
     * @param array<string|null> $scopes
     *   The scopes in which this attribute should apply.
    */
    public function __construct(
        string $primaryType,
        public readonly array $typeFields = [],
        array $scopes = [null],
    ) {
        parent::__construct($primaryType, $scopes);
    }

    public function fromReflection(\ReflectionProperty $subject): void
    {
        $this->typeDef ??= new TypeDef($subject->getType());
    }

    public function acceptsType(string $type): bool
    {
        return $this->typeDef->accepts($type);
    }

    public function validate(mixed $value): bool
    {
        return $this->typeDef->accepts(get_debug_type($value));
    }
}
