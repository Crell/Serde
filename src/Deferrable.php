<?php

declare(strict_types=1);

namespace Crell\Serde;

interface Deferrable
{
    public function setDeferrer(Decoder $decoder): void;
}
