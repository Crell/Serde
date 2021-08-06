<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;
use Crell\AttributeUtils\FromReflectionClass;
use Crell\AttributeUtils\ParseProperties;

/**
 * @internal
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ClassDef implements FromReflectionClass, ParseProperties
{
    /** @var Field[] */
    public array $properties;

    public function __construct(
        public ?string $name = null,
    ){}

    public function fromReflection(\ReflectionClass $subject): void
    {
        $this->name ??= $subject->getShortName();
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
