<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\Excludable;
use Crell\AttributeUtils\Finalizable;
use Crell\AttributeUtils\FromReflectionProperty;
use Crell\AttributeUtils\HasSubAttributes;
use Crell\AttributeUtils\ReadsClass;
use Crell\AttributeUtils\SupportsScopes;
use Crell\fp\Evolvable;
use Crell\Serde\FieldTypeIncompatible;
use Crell\Serde\IntersectionTypesNotSupported;
use Crell\Serde\PropValue;
use Crell\Serde\Renaming\LiteralName;
use Crell\Serde\Renaming\RenamingStrategy;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeField;
use Crell\Serde\TypeMap;
use Crell\Serde\UnionTypesNotSupported;
use Crell\Serde\UnsupportedType;
use function Crell\fp\indexBy;
use function Crell\fp\method;
use function Crell\fp\pipe;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Field implements FromReflectionProperty, HasSubAttributes, Excludable, SupportsScopes, ReadsClass, Finalizable
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
     * Whether null is an acceptable value or not.
     *
     * At the moment, this is the only union-type that is supported.
     */
    public readonly bool $nullable;

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
     * Whether or not to require a value when deserializing into this field.
     */
    public readonly bool $requireValue;

    /**
     * The renaming mechanism used for this field.
     *
     * This property is unset after the analysis phase to minimize
     * the serialized size of this object.
     */
    protected ?RenamingStrategy $rename;

    /**
     * Whether or not to omit values when serializing if they are null.
     */
    public readonly bool $omitIfNull;


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
     * @param string $flattenPrefix
     *   If the field is flattened, this string will be prepended to the name of every field in the sub-value.
     *   If not flattened, this field is ignored.
     * @param bool $exclude
     *   Set true to exclude this field from serialization entirely.
     * @param string[] $alias
     *   On deserialization, also check for values in fields with these names.
     * @param bool $strict
     *   On deserialization, set to true to require incoming data to be of the
     *   correct type. If false, the system will attempt to cast values to
     *   the correct type.
     * @param bool $requireValue
     *   On deserialization, set to true to require incoming data to have a value.
     *   If it does not, and incoming data is missing a value for this field, and
     *   no default is set for the property, then an exception will be thrown.  Set
     *   to false to disable this check, in which case the value may be uninitialized
     *   after deserialization.  If a property has a default value, this directive
     *   has no effect.
     * @param bool $omitIfNull
     *   When serializing, if a property is set to null, exclude it from the output
     *   entirely.  Default false, meaning a "null" will be written to the output format.
     * @param array<string|null> $scopes
     *   If specified, this Field entry will be included only when operating in
     *   the specified scopes.  To also be included in the default "unscoped" case,
     *   include an array element of `null`, or include a non-scoped copy of the
     *   Field.
     */
    public function __construct(
        ?string $serializedName = null,
        ?RenamingStrategy $renameWith = null,
        mixed $default = PropValue::None,
        protected readonly bool $useDefault = true,
        public readonly bool $flatten = false,
        public readonly string $flattenPrefix = '',
        public readonly bool $exclude = false,
        public readonly array $alias = [],
        public readonly bool $strict = true,
        ?bool $requireValue = null,
        ?bool $omitIfNull = null,
        protected readonly array $scopes = [null],
    ) {
        if ($default !== PropValue::None) {
            $this->defaultValue = $default;
            $this->shouldUseDefault = true;
        }
        // Null means we want to accept a default value later from the class.
        if ($requireValue !== null) {
            $this->requireValue = $requireValue;
        }
        if ($omitIfNull !== null) {
            $this->omitIfNull = $omitIfNull;
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

        // An untyped property is equivalent to mixed, which is nullable.
        // Damnit, PHP.
        $this->nullable = $subject->getType()?->allowsNull() ?? true;

        // This abomination is the only way to differentiate uninitialized from "set to null".
        if (!array_key_exists('defaultValue', get_object_vars($this))) {
            // We only care about defaults from the constructor; if a non-readonly property
            // has a default value, then newInstanceWithoutConstructor() will use it for us
            // and we don't need to do anything.
            $constructorDefault = $this->getDefaultValueFromConstructor($subject);

            $this->shouldUseDefault
                ??= $this->useDefault && $constructorDefault !== PropValue::None;

            if ($this->shouldUseDefault) {
                $this->defaultValue ??= $constructorDefault;
            }
        }
    }

    /**
     * @param ClassSettings $class
     */
    public function fromClassAttribute(object $class): void
    {
        // If there is no requireValue flag set, inherit it from the class attribute.
        $this->requireValue ??= $class->requireValues;
        $this->rename ??= $class->renameWith ?? null;
        $this->omitIfNull ??= $class->omitNullFields ?? false;
    }

    protected function getDefaultValueFromConstructor(\ReflectionProperty $subject): mixed
    {
        // A static value, so it's cached but not included in the Field object when serializing.
        static $params = [];

        $declaringClass = $subject->getDeclaringClass();
        /** @var array<string, \ReflectionParameter> $params */
        $params[$declaringClass->getName()] ??= pipe($declaringClass->getConstructor()?->getParameters() ?? [],
            indexBy(method('getName')),
        );

        $param = $params[$declaringClass->getName()][$subject->getName()] ?? null;

        return $param?->isDefaultValueAvailable() && $param->isPromoted()
            ? $param->getDefaultValue()
            : PropValue::None;
    }

    public function finalize(): void
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
            TypeMap::class => fn(?TypeMap $map) => $this->typeMap = $map,
            TypeField::class => $this->fromTypeField(...),
        ];
    }

    protected function fromTypeField(?TypeField $typeField): void
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
        ?TypeField $typeField = null,
        ?string $phpName = null,
    ): self
    {
        $new = new self();
        $new->serializedName = $serializedName;
        $new->phpName = $phpName ?? $serializedName;
        if ($phpType) {
            $new->phpType = $phpType;
        }
        $new->typeMap = null;
        $new->typeField = $typeField;
        $new->extraProperties = $extraProperties;
        // @todo Is there a case where we would want to make this configurable? I don't think so.
        $new->nullable = false;
        $new->finalize();
        return $new;
    }

    protected function deriveTypeCategory(): TypeCategory
    {
        return match (true) {
            in_array($this->phpType, ['int', 'float', 'bool', 'string'], true) => TypeCategory::Scalar,
            $this->phpType === 'array' => TypeCategory::Array,
            $this->phpType === 'iterable', is_a($this->phpType, \Generator::class, true) => TypeCategory::Generator,
            \enum_exists($this->phpType) => $this->enumType($this->phpType),
            $this->phpType === 'object', \class_exists($this->phpType), \interface_exists($this->phpType) => TypeCategory::Object,
            $this->phpType === 'null' => TypeCategory::Null,
            $this->phpType === 'mixed' => TypeCategory::Mixed,
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
        } elseif ($this->nullable && $valueType === 'null') {
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
