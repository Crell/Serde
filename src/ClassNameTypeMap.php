<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;

/**
 * A special case of a type map where the class name is its own identifier.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class ClassNameTypeMap implements TypeMapper
{
    public function __construct(
        public readonly string $key,
    ) {}

    public function keyField(): string
    {
        return $this->key;
    }

    public function findClass(string $id): ?string
    {
        return $id;
    }

    public function findIdentifier(string $class): ?string
    {
        return $class;
    }
}
