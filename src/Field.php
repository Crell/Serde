<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;
use Crell\AttributeUtils\FromReflectionProperty;
use Crell\Serde\IntersectionTypesNotSupported;
use Crell\Serde\UnionTypesNotSupported;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Field implements FromReflectionProperty
{
    /**
     * The native PHP type, as the reflection system defines it.
     */
    public string $phpType;

    /**
     * The property name, not to be confused with the desired serialized $name.
     */
    public string $phpName;

    public const TYPE_NOT_SPECIFIED = '__NO_TYPE__';

    public function __construct(
        public ?string $name = null,
        public ?string $default = null,
    ) {}

    public function fromReflection(\ReflectionProperty $subject): void
    {
        $this->phpName = $subject->name;
        $this->name ??= $subject->name;
        $this->phpType ??= $this->getNativeType($subject);
        $this->default ??= $subject->getDefaultValue();
    }

    protected function getNativeType(\ReflectionProperty $property): string
    {
        // @todo Support easy unions, like int|float
        $rType = $property->getType();
        return match(true) {
            $rType instanceof \ReflectionUnionType => throw UnionTypesNotSupported::create($property),
            $rType instanceof \ReflectionIntersectionType => throw IntersectionTypesNotSupported::create($property),
            $rType instanceof \ReflectionNamedType => $rType->getName(),
            default => static::TYPE_NOT_SPECIFIED,
        };
    }
}
