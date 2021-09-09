<?php

declare(strict_types=1);

namespace Crell\Serde\Decoder;

use Crell\Serde\Decoder;

/**
 * @todo This is a poor name.
 */
trait Delegator
{
    protected Decoder $deferrer;

    public function setDelegationTarget(Decoder $decoder): void
    {
        $this->deferrer = $decoder;
    }
}
