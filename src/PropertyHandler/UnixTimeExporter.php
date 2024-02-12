<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;
use Crell\Serde\Serializer;

class UnixTimeExporter implements Importer, Exporter {

    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {

    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        // TODO: Implement canExport() method.
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        // TODO: Implement importValue() method.
    }

    public function canImport(Field $field, string $format): bool
    {
        // TODO: Implement canImport() method.
    }
}
