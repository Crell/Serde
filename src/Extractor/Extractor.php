<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;

interface Extractor
{
    public function extract(
        JsonFormatter $formatter,
        string $format,
        string $name,
        mixed $value,
        Field $field,
        mixed $runningValue
    ): mixed;

    public function supportsExtract(Field $field, mixed $value, string $format): bool;

}
