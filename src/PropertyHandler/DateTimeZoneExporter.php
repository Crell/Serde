<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;

class DateTimeZoneExporter implements Exporter, Importer
{
    /**
     * @param Serializer $serializer
     * @param Field $field
     * @param \DateTimeZone $value
     * @param mixed $runningValue
     * @return mixed
     */
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $string = $value->getName();
        return $serializer->formatter->serializeString($runningValue, $field, $string);
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === \DateTimeZone::class;
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        $string = $deserializer->deformatter->deserializeString($source, $field);

        if ($string instanceof SerdeError) {
            return null;
        }

        return new \DateTimeZone($string);
    }

    public function canImport(Field $field, string $format): bool
    {
        return $field->phpType === \DateTimeZone::class;
    }
}
