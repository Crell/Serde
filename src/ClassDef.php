<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;
use Crell\AttributeUtils\FromReflectionClass;
use Crell\AttributeUtils\HasSubAttributes;
use Crell\AttributeUtils\ParseProperties;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ClassDef implements FromReflectionClass, ParseProperties, HasSubAttributes
{
    use HasTypeMap;

    /** @var Field[] */
    public readonly array $properties;

    public function __construct(
        public ?string $name = null,
        public ?string $fullName = null,
    ){}

    public function fromReflection(\ReflectionClass $subject): void
    {
        $this->name ??= $subject->getShortName();
        $this->fullName ??= $subject->name;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function includeByDefault(): bool
    {
        return true;
    }

    public static function propertyAttribute(): string
    {
        return Field::class;
    }

}
