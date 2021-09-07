<?php

declare(strict_types=1);

namespace Crell\Serde\Decoder;

use Crell\Serde\AST\BooleanValue;
use Crell\Serde\Decoder;

class BooleanDecoder implements Decoder
{
    use Deferer;

    /**
     * @param bool $value
     * @return BooleanValue
     */
    public function decode(mixed $value): BooleanValue
    {
        return new BooleanValue($value);
    }
}
