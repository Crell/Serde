<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\AST\Value;

interface Decoder
{
    public function decode(mixed $value): Value;

    public function setDeferrer(Decoder $decoder): void;
}
