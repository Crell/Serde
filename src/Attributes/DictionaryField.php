<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\SupportsScopes;
use Crell\Serde\TypeField;
use function Crell\fp\amapWithKeys;
use function Crell\fp\explode;
use function Crell\fp\implode;
use function Crell\fp\pipe;
use function Crell\fp\reduce;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DictionaryField implements TypeField, SupportsScopes
{
    /**
     * @param string|null $arrayType
     *   Elements in this array are objects of this type.
     * @param string|null $implodeOn
     *   Scalar values of this array should be imploded to a string and exploded on deserialization.
     * @param string|null $joinOn
     *   The key and value of each element will be concatenated with this character when imploding.
     * @param bool $trim
     *   When exploding a string back to an array, trim() each value. Has no effect if $implodeOn is not set.
     * @param array<string|null> $scopes
     *   The scopes in which this attribute should apply.
     */
    public function __construct(
        public readonly ?string $arrayType = null,
        public readonly ?string $implodeOn = null,
        public readonly ?string $joinOn = null,
        public readonly bool $trim = true,
        protected readonly array $scopes = [null],
    ) {}

    /**
     * @return array<string|null>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function shouldImplode(): bool
    {
        return $this->implodeOn && $this->joinOn;
    }

    /**
     * @param array<string, string> $array
     * @return string
     */
    public function implode(array $array): string
    {
        return pipe($array,
            amapWithKeys(fn ($v, $k) => "{$k}{$this->joinOn}{$v}"),
            implode($this->implodeOn),
        );
    }

    /**
     * @param string $in
     * @return array<string, string>
     */
    public function explode(string $in): array
    {
        return pipe($in,
            explode($this->implodeOn),
            reduce([], $this->explodeReduce(...)),
        );
    }

    /**
     * @param array<string, string> $array
     * @param string $item
     * @return array<string, string>
     */
    protected function explodeReduce(array $array, string $item): array
    {
        if (!$item) {
            return $array;
        }
        if (!\str_contains($item, $this->joinOn)) {
            $k = $this->trim ? trim($item) : $item;
            $array[$k] = '';
            return $array;
        }

        // PHPStan thinks $item could be null here.  PHPStan is wrong.
        // @phpstan-ignore-next-line
        [$k, $v] = \explode($this->joinOn, $item);
        if ($this->trim) {
            $k = trim($k);
            $v = trim($v);
        }
        $array[$k] = $v;
        return $array;
    }

    public function acceptsType(string $type): bool
    {
        return $type === 'array';
    }
}
