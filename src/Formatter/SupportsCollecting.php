<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

/**
 * Indicates a Deformatter that supports reading "the rest of the data" into a collected value.
 */
interface SupportsCollecting
{
    /**
     * @param mixed $source
     *   The deformatter-specific source value being passed around.
     * @param string[] $used
     *   A list of property names have have already been extracted, and so are
     *   not "remaining."
     * @return mixed
     */
    public function getRemainingData(mixed $source, array $used): mixed;
}
