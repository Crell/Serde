<?php

declare(strict_types=1);

namespace Crell\Serde\Decoder;

use Crell\Serde\AST\FloatValue;
use Crell\Serde\Decoder;

class FloatDecoder implements Decoder
{
    /**
     * @param float $value
     * @return FloatValue
     */
    public function decode(mixed $value): FloatValue
    {
        return new FloatValue($value);
    }
}
