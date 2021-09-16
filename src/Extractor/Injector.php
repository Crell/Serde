<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

interface Injector
{
    // @todo This method needs a better name.
    public function getValue(JsonFormatter $formatter, string $format, mixed $source, Field $field): mixed;

    public function supportsInject(Field $field, string $format): bool;
}
