<?php

declare(strict_types=1);

namespace Crell\Serde\Decoder;

use Crell\Serde\AST\IntegerValue;
use Crell\Serde\Decoder;

class IntegerDecoder implements Decoder
{
    use Delegator;

    /**
     * @param int $value
     * @return IntegerValue
     */
    public function decode(mixed $value): IntegerValue
    {
        return new IntegerValue($value);
    }
}
