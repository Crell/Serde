<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;
use Crell\AttributeUtils\FromReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Field implements FromReflectionProperty
{
    use Evolvable;

    /**
     * The native PHP type, as the reflection system defines it.
     */
    public string $phpType;

    /**
     * The property name, not to be confused with the desired serialized $name.
     */
    public string $phpName;

    /**
     * Cached copy of the serialized name this field should use.
     */
    protected string $cachedSerializedName;

    public const TYPE_NOT_SPECIFIED = '__NO_TYPE__';

    public function __construct(
        /** A custom name to use for this field */
        public ?string $serializedName = null,
        /** Specify a case folding strategy to use */
        public Cases $caseFold = Cases::Unchanged,
        /** Use this default value if none is specified. */
        //public mixed $default = null,
        /** True to flatten an array on serialization and collect into it when deserializing. */
        public bool $flatten = false,
        /** For an array property, specifies the class type of each item in the array. */
        public ?string $arrayType = null,
    ) {}

    public function fromReflection(\ReflectionProperty $subject): void
    {
        $this->phpName = $subject->name;
//        $this->renameTo ??= $subject->name;
        $this->phpType ??= $this->getNativeType($subject);
        $this->default ??= $subject->getDefaultValue();
    }

    /**
     * @internal
     *
     * This method is to allow the serializer to create new pseudo-Fields
     * for nested values when flattening and collecting. Do not call it directly.
     */
    public static function create(
        ?string $name = null,
        Cases $caseFold = Cases::Unchanged,
        string $phpName = null,
        string $phpType = null,
    ): static
    {
        $new = new static(serializedName: $name, caseFold: $caseFold);
        $new->phpType = $phpType;
        $new->phpName = $phpName;
        return $new;
    }

    protected function getNativeType(\ReflectionProperty $property): string
    {
        // @todo Support easy unions, like int|float.
        $rType = $property->getType();
        return match(true) {
            $rType instanceof \ReflectionUnionType => throw UnionTypesNotSupported::create($property),
            $rType instanceof \ReflectionIntersectionType => throw IntersectionTypesNotSupported::create($property),
            $rType instanceof \ReflectionNamedType => $rType->getName(),
            default => static::TYPE_NOT_SPECIFIED,
        };
    }

    public function serializedName(): string
    {
        return $this->cachedSerializedName ??= $this->deriveSerializedName();
    }

    protected function deriveSerializedName(): string
    {
        $name = $this->phpName;

        if ($this->serializedName) {
            $name = $this->serializedName;
        }

        if ($this->caseFold !== Cases::Unchanged) {
            $name = $this->caseFold->convert($name);
        }

        return $name;
    }
}
