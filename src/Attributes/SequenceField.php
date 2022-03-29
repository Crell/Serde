<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\SupportsScopes;
use Crell\Serde\TypeField;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SequenceField implements TypeField, SupportsScopes
{
    public function __construct(
        /** Elements in this array are objects of this type. */
        public readonly ?string $arrayType = null,
        /** Scalar values of this array should be imploded to a string and exploded on deserialization. */
        public readonly ?string $implodeOn = null,
        /** When exploding a string back to an array, trim() each value. Has no effect if $implodeOn is not set. */
        public readonly bool $trim = true,
        protected readonly array $scopes = [null],
    ) {}

    public function acceptsType(string $type): bool
    {
        return $type === 'array';
    }

    public function shouldImplode(): bool
    {
        return !is_null($this->implodeOn);
    }

    public function scopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param string[] $array
     */
    public function implode(array $array): string
    {
        return \implode($this->implodeOn, $array);
    }

    /**
     * @param string $in
     * @return string[]
     */
    public function explode(string $in): array
    {
        $parts = \explode($this->implodeOn, $in);
        if ($this->trim) {
            $parts = array_map(trim(...), $parts);
        }
        return array_filter($parts);
    }
}
