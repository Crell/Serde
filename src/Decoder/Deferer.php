<?php

declare(strict_types=1);

namespace Crell\Serde\Decoder;

use Crell\Serde\Decoder;

/**
 * @todo This is a poor name.
 */
trait Deferer
{
    protected Decoder $deferrer;

    public function setDeferrer(Decoder $decoder): void
    {
        $this->deferrer = $decoder;
    }
}
