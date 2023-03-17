<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\FromReflectionClass;
use Crell\AttributeUtils\HasSubAttributes;
use Crell\AttributeUtils\ParseMethods;
use Crell\AttributeUtils\ParseProperties;
use Crell\AttributeUtils\SupportsScopes;
use Crell\Serde\TypeMap;
use function Crell\fp\prop;

#[Attribute(Attribute::TARGET_CLASS)]
class ClassSettings implements FromReflectionClass, ParseProperties, HasSubAttributes, ParseMethods, SupportsScopes
{
    /**
     * The type map, if any, that applies to this class.
     */
    public readonly ?TypeMap $typeMap;

    /** @var array<string, Field> */
    #[DictionaryField(arrayType: Field::class)]
    public readonly array $properties;

    public readonly string $phpType;

    /** @var string[] */
    #[SequenceField]
    public readonly array $postLoadCallacks;

    /**
     * @param bool $includeFieldsByDefault
     *   If true, all fields will be included when serializing and deserializing unless
     *   the individual field opts-out with #[Field(exclude: true)].  If false, all fields
     *   will be ignored unless they have a #[Field] directive.
     * @param array<string|null> $scopes
     *   If specified, this ClassSettings entry will be included only when operating in
     *   the specified scopes.  To also be included in the default "unscoped" case,
     *   include an array element of `null`, or include a non-scoped copy of the
     *   Field.
     * @param bool $requireValues
     *   If true, all fields will be required when deserializing into this object.
     *   If false, fields will not be required and unset fields will be left uninitialized.
     *   this may be overridden on a per-field basis with #[Field(requireValue: true)]
     */
    public function __construct(
        public readonly bool $includeFieldsByDefault = true,
        public readonly array $scopes = [null],
        public readonly bool $requireValues = false,
    ) {}

    public function fromReflection(\ReflectionClass $subject): void
    {
        $this->phpType ??= $subject->getName();
    }

    /**
     * @return array<string|null>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param array<string, Field> $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function includePropertiesByDefault(): bool
    {
        return $this->includeFieldsByDefault;
    }

    public function propertyAttribute(): string
    {
        return Field::class;
    }

    public function subAttributes(): array
    {
        return [TypeMap::class => 'fromTypeMap'];
    }

    public function fromTypeMap(?TypeMap $map): void
    {
        // This may assign to null, which is OK as that will
        // evaluate to false when we need it to.
        $this->typeMap = $map;
    }

    /**
     * @param MethodSettings[] $methods
     */
    public function setMethods(array $methods): void
    {
        $this->postLoadCallacks = array_keys(
            array_filter($methods, prop('postLoadCallback'))
        );
    }

    public function includeMethodsByDefault(): bool
    {
        return true;
    }

    public function methodAttribute(): string
    {
        return MethodSettings::class;
    }
}
