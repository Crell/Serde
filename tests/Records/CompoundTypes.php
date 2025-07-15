<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\MixedField;

class CompoundTypes
{
    public function __construct(
        #[MixedField(ClassWithInterfaces::class)]
        public string|(InterfaceA&InterfaceB) $value,
    ) {}
}
