<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\UnixTime;
use Crell\Serde\DeformatterResult;
use Crell\Serde\Deserializer;
use Crell\Serde\Serializer;

class UnixTimeExporter implements Importer, Exporter {

    /**
     * @param Serializer $serializer
     * @param Field $field
     * @param \DateTimeInterface $value
     * @param mixed $runningValue
     * @return mixed
     */
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        /** @var UnixTime|null $typeField */
        $typeField = $field->typeField;

        $multiplier = $typeField->milliseconds ? 1000 : 1;


        return $serializer->formatter->serializeInt($runningValue, $field, $value->getTimestamp() * $multiplier);
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $field->typeField instanceof UnixTime && $value instanceof \DateTimeInterface;
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        /** @var UnixTime|null $typeField */
        $typeField = $field->typeField;

        $timestamp = $deserializer->deformatter->deserializeInt($source, $field);

        if($timestamp === DeformatterResult::Missing) {
            return null;
        }

        $divider = $typeField->milliseconds ? 1000.0 : 1.0;

        return new ($field->phpType)('@' . ($timestamp / $divider));
    }

    public function canImport(Field $field, string $format): bool
    {
        return $field->typeField instanceof UnixTime && in_array($field->phpType, [\DateTimeInterface::class, \DateTime::class, \DateTimeImmutable::class], true);
    }
}
