<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Deserializer;
use Crell\Serde\Field;

interface PropertyWriter
{
    public function writeValue(Deserializer $deserializer, Field $field, mixed $source): mixed;

    public function canWrite(Field $field, string $format): bool;
}
