<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\Field;

/**
 * Entry in a sequence or dictionary.
 *
 * Items in a sequence or dictionary require
 * more type metadata than PHP itself holds,
 * so this object acts as a carrier for that
 * extended data.
 *
 * @see Dict
 * @see Sequence
 */
class CollectionItem
{
    public function __construct(
        public readonly Field $field,
        public readonly mixed $value,
    ) {
    }
}
