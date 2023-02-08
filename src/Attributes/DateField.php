<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\SupportsScopes;
use Crell\Serde\TypeField;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DateField implements TypeField, SupportsScopes
{
    /**
     * @param string $format
     *   The format to use when exporting the field.  This may be any date format string recognized by PHP.
     * @param string|null $timezone
     *   The timezone string, like "America/Chicago" or "UTC", to which to force the value when exporting.
     * @param array<string|null> $scopes
     */
    public function __construct(
        public readonly string $format = \DateTimeInterface::RFC3339_EXTENDED,
        public readonly ?string $timezone = null,
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
        $ret = is_a($type, \DateTimeInterface::class, true);

        return $ret;
    }

    public function validate(mixed $value): bool
    {
        // Nothing much to do here beyond the type.
        return true;
    }

}
