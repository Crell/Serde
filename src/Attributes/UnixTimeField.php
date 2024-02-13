<?php


declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\SupportsScopes;
use Crell\Serde\Attributes\Enums\UnixTimeResolution;
use Crell\Serde\TypeField;

#[Attribute(Attribute::TARGET_PROPERTY)]
class UnixTimeField implements TypeField, SupportsScopes
{
    /**
     * @param UnixTimeResolution $resolution
     *   The resolution of the timestamp.
     * @param array<string|null> $scopes
     */
    public function __construct(
        public readonly UnixTimeResolution $resolution = UnixTimeResolution::Seconds,
        protected readonly array $scopes = [null],
    ) {}

    /**
     * @return array<string|null>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function acceptsType(string $type): bool
    {
        return is_a($type, \DateTimeInterface::class, true);
    }

    public function validate(mixed $value): bool
    {
        // Nothing much to do here beyond the type.
        return true;
    }
}
