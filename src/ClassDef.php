<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;
use Crell\AttributeUtils\FromReflectionClass;
use Crell\AttributeUtils\HasSubAttributes;
use Crell\AttributeUtils\ParseMethods;
use Crell\AttributeUtils\ParseProperties;

#[Attribute(Attribute::TARGET_CLASS)]
class ClassDef implements FromReflectionClass, ParseProperties, HasSubAttributes, ParseMethods
{
    /**
     * The type map, if any, that applies to this class.
     */
    public readonly ?TypeMap $typeMap;

    /** @var Field[] */
    public readonly array $properties;

    public readonly string $phpType;

    /** @var string[] */
    public readonly array $postLoadCallacks;

    public function __construct(
        public readonly bool $includeFieldsByDefault = true,
    ) {}

    public function fromReflection(\ReflectionClass $subject): void
    {
        $this->phpType ??= $subject->getName();
    }

    /**
     * @param Field[] $properties
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
     * @param MethodDef[] $methods
     */
    public function setMethods(array $methods): void
    {
        $this->postLoadCallacks = array_keys(
            array_filter($methods, static fn (MethodDef $def) => $def->postLoadCallback)
        );
    }

    public function includeMethodsByDefault(): bool
    {
        return true;
    }

    public function methodAttribute(): string
    {
        return MethodDef::class;
    }
}
