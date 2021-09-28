<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

interface SupportsCollecting
{
    public function getRemainingData(mixed $source, array $used): mixed;
}
