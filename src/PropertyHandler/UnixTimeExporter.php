<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Enums\UnixTimeResolution;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\UnixTimeField;
use Crell\Serde\DeformatterResult;
use Crell\Serde\Deserializer;
use Crell\Serde\Serializer;
use Crell\Serde\UnixTimestampOutOfRange;

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
        $resolution = $typeField?->resolution ?? UnixTimeResolution::Seconds;
        $multiplier = $resolution->value;

        // If the resulting value is going to be outside of the Integer range, just reject it. You can't be that precise
        // that far in the future or past.
        $seconds = $value->getTimestamp();
        if ($seconds > PHP_INT_MAX/$multiplier + 1 || $seconds < PHP_INT_MIN/$multiplier -1) {
            throw UnixTimestampOutOfRange::create($value, $resolution);
        }

        // The value to serialize is the number of seconds, shifted left by the multiplier,
        // plus the remainder.  The 'u' format is microseconds, so we need to divide by 1000
        // to get milliseconds.  Casting to an int acts as a round-down, which is close enough
        // to accurate 99% of the time.
        $toSerialize = match ($resolution) {
            UnixTimeResolution::Seconds => $seconds,
            UnixTimeResolution::Microseconds => $seconds * $multiplier + (int)$value->format('u'),
            UnixTimeResolution::Milliseconds => $seconds * $multiplier + (int)($value->format('u')/1000),
        };

        return $serializer->formatter->serializeInt($runningValue, $field, $toSerialize);
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
