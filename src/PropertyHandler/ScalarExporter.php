<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;

class ScalarExporter implements Exporter, Importer
{
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        return match ($field->phpType) {
            'int' => $serializer->formatter->serializeInt($runningValue, $field, $value),
            'float' => $serializer->formatter->serializeFloat($runningValue, $field, $value),
            'bool' => $serializer->formatter->serializeBool($runningValue, $field, $value),
            'string' => $serializer->formatter->serializeString($runningValue, $field, $value),
        };
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        return match ($field->phpType) {
            'int' => $deserializer->deformatter->deserializeInt($source, $field),
            'float' => $deserializer->deformatter->deserializeFloat($source, $field),
            'bool' => $deserializer->deformatter->deserializeBool($source, $field),
            'string' => $deserializer->deformatter->deserializeString($source, $field),
        };
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Scalar;
    }

    public function canImport(Field $field, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Scalar;
    }
}
