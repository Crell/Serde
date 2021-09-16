<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

interface PropertyWriter
{
    public function writeValue(JsonFormatter $formatter, callable $recursor, mixed $source, Field $field): mixed;

    public function canWrite(Field $field, string $format): bool;
}
