<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;
use Crell\Serde\DeformatterResult;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;

class NullExporter implements Importer, Exporter
{
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        return $serializer->formatter->serializeNull($runningValue, $field, $value);
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $value === null;
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): ?DeformatterResult
    {
        return $deserializer->deformatter->deserializeNull($source, $field);
    }

    public function canImport(Field $field, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Null;
    }
}
