<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\Enums\UnixTimeResolution;
use DateTimeInterface;
use InvalidArgumentException;

class UnixTimestampOutOfRange extends InvalidArgumentException implements SerdeException
{
    public readonly DateTimeInterface $timestamp;
    public readonly UnixTimeResolution $resolution;

    public static function create(DateTimeInterface $timestamp, UnixTimeResolution $resolution): self
    {
        $new = new self();
        $new->timestamp = $timestamp;
        $new->resolution = $resolution;

        $new->message = sprintf('The timestamp %s is out of the supported range for Unix timestamps when used with %s resolution.  Either use a less-precise resolution, or use a DateTimeField instead and serialize it in a more robust string format.', $timestamp->format('c'), $resolution->name);

        return $new;
    }
}
