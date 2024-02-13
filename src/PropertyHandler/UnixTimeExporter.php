<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\UnixTimeField;
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
        /** @var UnixTimeField|null $typeField */
        $typeField = $field->typeField;

        $multiplier = $typeField?->resolution->value;
        $timestamp = (float) $value->format('U.u');

        return $serializer->formatter->serializeInt($runningValue, $field, (int) ($timestamp * $multiplier));
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $field->typeField instanceof UnixTimeField && $value instanceof \DateTimeInterface;
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        /** @var UnixTimeField|null $typeField */
        $typeField = $field->typeField;

        $timestamp = $deserializer->deformatter->deserializeInt($source, $field);

        if($timestamp === DeformatterResult::Missing) {
            return null;
        }

        $divisor = $typeField?->resolution->value ?? 1;

        // We use number_format to truncate the number at 6 decimals since PHP doesn't support nanosecond precision.
        // We also specify the decimal separator and thousands separator in case the current locale would have different
        // settings.
        $timestamp = number_format($timestamp / $divisor, 6, '.', '');

        return new ($field->phpType)('@' . $timestamp);
    }

    public function canImport(Field $field, string $format): bool
    {
        return $field->typeField instanceof UnixTimeField && in_array($field->phpType, [\DateTimeInterface::class, \DateTime::class, \DateTimeImmutable::class], true);
    }
}
