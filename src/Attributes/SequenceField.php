<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\SupportsScopes;
use Crell\Serde\TypeField;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SequenceField implements TypeField, SupportsScopes
{
    /**
     * @param string|null $arrayType
     *   Elements in this array are objects of this type.
     * @param string|null $implodeOn
     *   Scalar values of this array should be imploded to a string and exploded on deserialization.
     * @param bool $trim
     *   When exploding a string back to an array, trim() each value. Has no effect if $implodeOn is not set.
     * @param array<string|null> $scopes
     *   The scopes in which this attribute should apply.
     */
    public function __construct(
        public readonly ?string $arrayType = null,
        public readonly ?string $implodeOn = null,
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

    /**
     * @return array<string|null>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param string[] $array
     */
    public function implode(array $array): string
    {
        // This property is guaranteed to have a value by now in practice.
        // @phpstan-ignore-next-line
        return \implode($this->implodeOn, $array);
    }

    /**
     * @param string $in
     * @return string[]
     */
    public function explode(string $in): array
    {
        // This property is guaranteed to have a value by now in practice.
        // @phpstan-ignore-next-line
        $parts = \explode($this->implodeOn, $in);
        if ($this->trim) {
            $parts = array_map(trim(...), $parts);
        }
        return array_filter($parts);
    }

    /**
     * @param array<mixed> $value
     * @return bool
     */
    public function validate(mixed $value): bool
    {
        return array_is_list($value);
    }
}
