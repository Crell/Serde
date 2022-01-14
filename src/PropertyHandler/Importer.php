<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;

interface Importer
{
    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed;

    public function canImport(Field $field, string $format): bool;
}
