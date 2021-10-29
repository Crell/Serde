<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SequenceField implements TypeField
{
    public function __construct(
        public readonly ?string $arrayType = null,
    ) {}
}
