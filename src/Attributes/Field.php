<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\Excludable;
use Crell\AttributeUtils\FromReflectionProperty;
use Crell\AttributeUtils\HasSubAttributes;
use Crell\AttributeUtils\SupportsScopes;
use Crell\fp\Evolvable;
use Crell\Serde\FieldTypeIncompatible;
use Crell\Serde\IntersectionTypesNotSupported;
use Crell\Serde\Renaming\LiteralName;
use Crell\Serde\Renaming\RenamingStrategy;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeField;
use Crell\Serde\TypeMap;
use Crell\Serde\UnionTypesNotSupported;
use Crell\Serde\UnsupportedType;
use function Crell\fp\indexBy;
use function Crell\fp\method;
use function Crell\fp\pipe;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Field implements FromReflectionProperty, HasSubAttributes, Excludable, SupportsScopes
{
    use Evolvable;

    /**
     * The type map, if any, that applies to this field.
     */
    public readonly ?TypeMap $typeMap;

    /**
     * The type field, if any, that applies to this field.
     *
     * This property holds any type-specific information defined.
     */
    public readonly ?TypeField $typeField;

    /**
     * The native PHP type, as the reflection system defines it.
     *
     * @var string|class-string
     */
    public readonly string $phpType;

    /**
     * The property name, not to be confused with the desired serialized $name.
     */
    public readonly string $phpName;

    /**
     * The category of type this Field refers to.
     */
    public readonly TypeCategory $typeCategory;

    /**
     * The serialized name of this field.
     */
    public readonly string $serializedName;

    /**
     * The default value this field should be assigned, if any.
     */
    public readonly mixed $defaultValue;

    /**
     * Whether or not to use the code-defined default on deserialization if a value is not provided.
     */
    public readonly bool $shouldUseDefault;

    /**
     * The renaming mechanism used for this field.
     *
     * This property is unset after the analysis phase to minimize
     * the serialized size of this object.
     */
    protected ?RenamingStrategy $rename;

    /**
     * Additional key/value pairs to be included with an object.
     *
     * Only viable on object properties, and really not something
     * you should use yourself.
     *
     * @internal
     *
     * @var array<string, mixed>
     */
    public readonly array $extraProperties;

    public const TYPE_NOT_SPECIFIED = '__NO_TYPE__';

    /**
     * @param string|null $serializedName
     *   A custom name to use for this field.
     * @param RenamingStrategy|null $renameWith
     *   Specify a field renaming strategy. Usually you can use the Cases enum.
     * @param mixed|null $default
     *   Use this default value if none is specified.
     * @param bool $useDefault
     *   True to use the default value on deserialization. False to skip setting it entirely.
     * @param bool $flatten
     *   True to flatten an array on serialization and collect into it when deserializing.
     * @param bool $exclude
     *   Set true to exclude this field from serialization entirely.
     * @param string[] $alias
     *   On deserialization, also check for values in fields with these names.
     * @param bool $strict
     *   On deserialization, set to true to require incoming data to be of the
     *   correct type. If false, the system will attempt to cast values to
     *   the correct type.
     * @param array<string|null> $scopes
     *   If specified, this Field entry will be included only when operating in
     *   the specified scopes.  To also be included in the default "unscoped" case,
     *   include an array element of `null`, or include a non-scoped copy of the
     *   Field.
     */
    public function __construct(
        ?string $serializedName = null,
        ?RenamingStrategy $renameWith = null,
        mixed $default = null,
        protected readonly bool $useDefault = true,
        public readonly bool $flatten = false,
        public readonly bool $exclude = false,
        public readonly array $alias = [],
        public readonly bool $strict = true,
        protected readonly array $scopes = [null],
    ) {
        if ($default) {
            $this->defaultValue = $default;
        }
        // Upcast the literal serialized name to a converter if appropriate.
        $this->rename ??=
            $renameWith
            ?? ($serializedName ? new LiteralName($serializedName) : null);
    }

    /**
     * @return array<string|null>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function fromReflection(\ReflectionProperty $subject): void
    {
        $this->phpName = $subject->name;
        $this->phpType ??= $this->getNativeType($subject);

        $constructorDefault = $this->getDefaultValueFromConstructor($subject);

        $this->shouldUseDefault
            ??= $this->useDefault
            && ($subject->hasDefaultValue() || $constructorDefault !== SerdeError::NoDefaultValue)
        ;

        if ($this->shouldUseDefault) {
            $this->defaultValue
                ??= $subject->getDefaultValue()
                ?? $constructorDefault
            ;
        }

        $this->finalize();
    }

    protected function getDefaultValueFromConstructor(\ReflectionProperty $subject): mixed
    {
        /** @var array<string, \ReflectionParameter> $params */
        $params = pipe($subject->getDeclaringClass()->getConstructor()?->getParameters() ?? [],
            indexBy(method('getName')),
        );

        $param = $params[$subject->getName()] ?? null;

        return $param?->isDefaultValueAvailable()
            ? $param->getDefaultValue()
            : SerdeError::NoDefaultValue;
    }

    protected function finalize(): void
    {
        // We cannot compute these until we have the PHP type,
        // but they can still be determined entirely at analysis time
        // and cached.
        $this->typeCategory ??= $this->deriveTypeCategory();
        $this->serializedName ??= $this->deriveSerializedName();

        // Ensure a type-safe default.
        $this->extraProperties ??= [];

        // We don't need this object anymore, so clear it to minimize
        // the serialized size of this object.
        unset($this->rename);
    }

    protected function enumType(string $phpType): TypeCategory
    {
        // The Reflector interface is insufficient, but getName() is defined
        // on all types we care about. This is a reflection API limitation.
        // @phpstan-ignore-next-line
        return match ((new \ReflectionEnum($phpType))?->getBackingType()?->getName()) {
            'int' => TypeCategory::IntEnum,
            'string' => TypeCategory::StringEnum,
            null => TypeCategory::UnitEnum,
        };
    }

    public function subAttributes(): array
    {
        return [
            TypeMap::class => 'fromTypeMap',
            TypeField::class => 'fromTypeField',
        ];
    }

    public function fromTypeMap(?TypeMap $map): void
    {
        // This may assign to null, which is OK as that will
        // evaluate to false when we need it to.
        $this->typeMap = $map;
    }

    public function fromTypeField(?TypeField $typeField): void
    {
        if ($typeField && !$typeField->acceptsType($this->phpType)) {
            throw FieldTypeIncompatible::create($typeField::class, $this->phpType);
        }
        // This may assign to null, which is OK as that will
        // evaluate to false when we need it to.
        $this->typeField = $typeField;
    }

    /**
     * This method is to allow the serializer to create new pseudo-Fields
     * for nested values when flattening and collecting. Do not call it directly.
     *
     * @internal
     *
     * @param string $serializedName
     * @param string|null $phpType
     * @param array<string, mixed> $extraProperties
     * @return Field
     */
    public static function create(
        string $serializedName,
        ?string $phpType = null,
        array $extraProperties = [],
        TypeField $typeField = null,
    ): self
    {
        $new = new self();
        $new->serializedName = $serializedName;
        $new->phpName = $serializedName;
        if ($phpType) {
            $new->phpType = $phpType;
        }
        $new->typeMap = null;
        $new->typeField = $typeField;
        $new->extraProperties = $extraProperties;
        $new->finalize();
        return $new;
    }

    protected function deriveTypeCategory(): TypeCategory
    {
        return match (true) {
            in_array($this->phpType, ['int', 'float', 'bool', 'string'], true) => TypeCategory::Scalar,
            $this->phpType === 'array' => TypeCategory::Array,
            \enum_exists($this->phpType) => $this->enumType($this->phpType),
            $this->phpType === 'object', \class_exists($this->phpType), \interface_exists($this->phpType) => TypeCategory::Object,
            default => throw UnsupportedType::create($this->phpType),
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

    public function deriveSerializedName(): string
    {
        return $this->rename?->convert($this->phpName)
            ?? $this->phpName;
    }

    public function exclude(): bool
    {
        return $this->exclude;
    }

    /**
     * Validates that the provided value is legal according to this field definition.
     */
    public function validate(mixed $value): bool
    {
        $valueType = \get_debug_type($value);

        if ($this->phpType === $valueType) {
            $valid = true;
        } elseif (is_object($value) || class_exists($this->phpType) || interface_exists($this->phpType)) {
            // For objects, do a type check and we're done.
            $valid = $value instanceof $this->phpType;
        } else {
            $valid = match ([$this->phpType, $valueType]) {
                // We don't want to allow numeric strings here,
                // because that should be handled by the formatter
                // and the strict mode flag, not here.
                ['int', 'string'] => false,
                // An int-esque float is fine; some formats auto-cast
                // like that when deformatting.
                ['int', 'float'] => floor($value) === $value,
                ['int', 'array'] => false,
                ['float', 'int'] => true,
                ['float', 'string'] => false,
                ['float', 'array'] => false,
                // Because PHP arrays don't preserve ints and floats in keys,
                // we have to allow strings to be more permissive.
                // It sucks, but such is PHP.
                ['string', 'int'] => true,
                ['string', 'float'] => true,
                ['string', 'array'] => false,
                // Arrays take nothing else, obviously.
                ['array', 'int'] => false,
                ['array', 'float'] => false,
                ['array', 'string'] => false,
                // Default to true to allow the typeField to take over
                // in any other case.
                default => true,
            };
        }

        // The value validates if it passes the simple check above,
        // plus the typeField check, if any.
        return $valid && ($this->typeField?->validate($value) ?? true);
    }
}
