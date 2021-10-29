<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DictionaryField implements TypeField
{
    public function __construct(
        public readonly ?string $arrayType = null,
    ) {}

    public function acceptsType(string $type): bool
    {
        return $type === 'array';
    }
}
