<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\SupportsScopes;
use Crell\Serde\TypeMap;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class StaticTypeMap implements TypeMap, SupportsScopes
{
    /**
     * @param string $key
     * @param array<string, class-string> $map
     * @param array<string|null> $scopes
     */
    public function __construct(
        public readonly string $key,
        public readonly array $map,
        protected readonly array $scopes = [null],
    ) {}

    /**
     * @return array<string|null>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function keyField(): string
    {
        return $this->key;
    }

    public function findClass(string $id): ?string
    {
        return $this->map[$id] ?? null;
    }

    public function findIdentifier(string $class): ?string
    {
        return array_search($class, $this->map, true) ?: null;
    }
}
