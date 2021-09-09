<?php

declare(strict_types=1);

namespace Crell\Serde;

interface Delegatable
{
    public function setDelegationTarget(Decoder $decoder): void;
}
