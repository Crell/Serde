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

    /** @var Field[] */
    public readonly array $properties;

    public readonly string $phpType;

    /** @var string[] */
    public readonly array $postLoadCallacks;

    /**
     * @param array<string|null> $scopes
     */
    public function __construct(
        public readonly bool $includeFieldsByDefault = true,
        public readonly array $scopes = [null],
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
