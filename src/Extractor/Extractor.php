<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\JsonFormatter;

interface Extractor
{
    public function extract(
        JsonFormatter $formatter,
        string $format,
        string $name,
        mixed $value,
        string $type,
        mixed $runningValue
    ): mixed;

    public function supportsExtract(string $type, mixed $value, string $format): bool;

}
