<?php

declare(strict_types = 1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\Field;

class FlattenedValueObjectMain
{
    public function __construct(
        #[Field(flatten: true, flattenPrefix: 'order_')]
        public readonly ?OrderId $orderId = null,
    ) {}
}
