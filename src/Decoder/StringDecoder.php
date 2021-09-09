<?php

declare(strict_types=1);

namespace Crell\Serde\Decoder;

use Crell\Serde\AST\StringValue;
use Crell\Serde\Decoder;

class StringDecoder implements Decoder
{
    /**
     * @param string $value
     * @return StringValue
     */
    public function decode(mixed $value): StringValue
    {
        return new StringValue($value);
    }
}
