<?php

declare(strict_types=1);

namespace Crell\Serde\Decoder;

use Crell\Serde\AST\DateTimeValue;
use Crell\Serde\AST\Value;
use Crell\Serde\Decoder;

class DateTimeImmutableDecoder implements Decoder
{
    /**
     * @param \DateTimeImmutable $object
     * @return DateTimeValue
     */
    public function decode(object $object): DateTimeValue
    {
        return new DateTimeValue(
            // The object is immutable so this is safe.
            // @todo We may want to manually provide a format instead of using 'c' to skip the empty offset.
            dateTime: $object->setTimezone(new \DateTimeZone('UTC'))->format('c'),
            dateTimeZone: $object->getTimezone()->getName(),
            immutable: true,
        );
    }
}

