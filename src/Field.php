<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;
use Crell\AttributeUtils\FromReflectionProperty;
use Crell\AttributeUtils\HasSubAttributes;
use Crell\Serde\Renaming\Cases;
use Crell\Serde\Renaming\LiteralName;
use Crell\Serde\Renaming\RenamingStrategy;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Field implements FromReflectionProperty, HasSubAttributes
{
    use Evolvable;
    use HasTypeMap;

    /**
     * The native PHP type, as the reflection system defines it.
     */
    public readonly string $phpType;

    /**
     * The property name, not to be confused with the desired serialized $name.
     */
    public readonly string $phpName;

    /**
     * Cached copy of the serialized name this field should use.
     */
    protected readonly string $serializedName;

    protected readonly ?RenamingStrategy $rename;

    public readonly TypeCategory $typeCategory;

    public const TYPE_NOT_SPECIFIED = '__NO_TYPE__';

    public function __construct(
        /** A custom name to use for this field */
        ?string $serializedName = null,
        ?RenamingStrategy $renameWith = null,
        /** Specify a case folding strategy to use */
        public Cases $caseFold = Cases::Unchanged,
        /** Use this default value if none is specified. */
        //public mixed $default = null,
        /** True to flatten an array on serialization and collect into it when deserializing. */
        public bool $flatten = false,
        /** For an array property, specifies the class type of each item in the array. */
        public ?string $arrayType = null,
    ) {
        // Upcast the literal serialized name to a converter if appropriate.
        $this->rename ??=
            $renameWith
            ?? ($serializedName ? new LiteralName($serializedName) : null);
    }

    public function fromReflection(\ReflectionProperty $subject): void
    {
        $this->phpName = $subject->name;
//        $this->renameTo ??= $subject->name;
        $this->phpType ??= $this->getNativeType($subject);
        $this->default ??= $subject->getDefaultValue();

        $this->typeCategory ??= $this->deriveTypeCategory();
    }

    protected function enumType(string $phpType): TypeCategory
    {
        return match ((new \ReflectionEnum($phpType))?->getBackingType()?->getName()) {
            'int' => TypeCategory::IntEnum,
            'string' => TypeCategory::StringEnum,
            null => TypeCategory::UnitEnum,
        };
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
        $new->typeCategory = $new->deriveTypeCategory();
        return $new;
    }

    protected function deriveTypeCategory(): TypeCategory
    {
        return match (true) {
            in_array($this->phpType, ['int', 'float', 'bool', 'string'], true) => TypeCategory::Scalar,
            $this->phpType === 'array' => TypeCategory::Array,
            \enum_exists($this->phpType) => $this->enumType($this->phpType),
            $this->phpType === 'object', \class_exists($this->phpType), \interface_exists($this->phpType) => TypeCategory::Object,
        };
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
        return $this->serializedName ??=
            $this->rename?->convert($this->phpName)
            ?? $this->phpName;
    }
}
