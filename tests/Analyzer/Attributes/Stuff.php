<?php

declare(strict_types=1);

namespace Crell\Serde\Analyzer\Attributes;

use Crell\AttributeUtils\Attributes\Reflect\CollectProperties;
use Crell\AttributeUtils\Attributes\Reflect\ReflectProperty;
use Crell\AttributeUtils\ParseProperties;
use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\SequenceField;

#[\Attribute]
class Stuff implements ParseProperties
{
    /** @var Stuff[] */
    #[DictionaryField(arrayType: Thing::class)]
    public readonly array $properties;

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function includePropertiesByDefault(): bool
    {
        return true;
    }

    public function __construct(
        public readonly string $a,
        public readonly string $b = '',
    ) {}

    public function propertyAttribute(): string
    {
        return Thing::class;
    }
}
