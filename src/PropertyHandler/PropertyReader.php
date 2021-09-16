<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

interface PropertyReader
{
    public function readValue(
        JsonFormatter $formatter,
        callable $recursor,
        Field $field,
        mixed $value,
        mixed $runningValue
    ): mixed;

    public function canRead(Field $field, mixed $value, string $format): bool;
}
