<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class MixedVal
{
    public function __construct(public mixed $val) {}
}
