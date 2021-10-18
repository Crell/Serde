<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;
use Crell\AttributeUtils\FromReflectionClass;
use Crell\AttributeUtils\HasSubAttributes;
use Crell\AttributeUtils\ParseProperties;

#[Attribute(Attribute::TARGET_CLASS)]
class ClassDef implements FromReflectionClass, ParseProperties, HasSubAttributes
{
    /**
     * The type map, if any, that applies to this class.
     */
    public readonly ?TypeMapper $typeMap;

    /** @var Field[] */
    public readonly array $properties;

    public readonly string $phpType;

    public function __construct(
        public readonly bool $includeFieldsByDefault = true,
    ) {}

    public function fromReflection(\ReflectionClass $subject): void
    {
        $this->phpType ??= $subject->getName();
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function includeByDefault(): bool
    {
        return $this->includeFieldsByDefault;
    }

    public static function propertyAttribute(): string
    {
        return Field::class;
    }

    public function subAttributes(): array
    {
        return [TypeMap::class => 'fromTypeMap'];
    }

    public function fromTypeMap(?TypeMapper $map): void
    {
        // This may assign to null, which is OK as that will
        // evaluate to false when we need it to.
        $this->typeMap = $map;
    }
}
