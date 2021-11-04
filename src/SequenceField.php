<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SequenceField implements TypeField
{
    public function __construct(
        /** Elements in this array are objects of this type. */
        public readonly ?string $arrayType = null,
        /** Scalar values of this array should be imploded to a string and exploded on deserialization. */
        public readonly ?string $implodeOn = null,
        /** When exploding a string back to an array, trim() each value. Has no effect if $implodeOn is not set. */
        public readonly bool $trim = true,
    ) {}

    public function acceptsType(string $type): bool
    {
        return $type === 'array';
    }

    public function shouldImplode(): bool
    {
        return !is_null($this->implodeOn);
    }

    public function implode(array $array): string
    {
        return \implode($this->implodeOn, $array);
    }

    public function explode(string $in): array
    {
        $parts = \explode($this->implodeOn, $in);
        if ($this->trim) {
            $parts = array_map(trim(...), $parts);
        }
        return array_filter($parts);
    }
}
