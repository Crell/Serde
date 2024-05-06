<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\DateField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;
use Crell\Serde\DeformatterResult;
use Crell\Serde\Serializer;

class DateTimeExporter implements Exporter, Importer
{
    /**
     * @param Serializer $serializer
     * @param Field $field
     * @param \DateTimeInterface $value
     * @param mixed $runningValue
     * @return mixed
     */
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        /** @var DateField|null $typeField */
        $typeField = $field->typeField;

        if ($timezone = $typeField?->timezone) {
            if ($value instanceof \DateTime) {
                // Seriously, who still uses DateTime?
                $value = clone($value);
                $value->setTimezone(new \DateTimeZone($timezone));
            } else {
                /** @var \DateTimeImmutable $value */
                $value = $value->setTimezone(new \DateTimeZone($timezone));
            }
        }

        $format = $typeField?->format ?? \DateTimeInterface::RFC3339_EXTENDED;

        $string = $value->format($format);
        return $serializer->formatter->serializeString($runningValue, $field, $string);
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $value instanceof \DateTimeInterface;
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        $string = $deserializer->deformatter->deserializeString($source, $field);

        if ($string === DeformatterResult::Missing || $string === null) {
            return null;
        }

        // @todo It would be helpful to use https://www.php.net/manual/en/datetimeimmutable.createfromformat.php
        // to only accept value sin the expected format. However, that method
        // auto-fills missing values with the current time.  The constructor does
        // not.  So if you have a Y-m-d string of 2022-07-04T00:00:00 and create a new DTI
        // using the constructor, you get that timestamp. If you use createFromFormat(),
        // you get that date but the time is filled in with whenever you ran the code.
        // Once we figure out how to deal with that (arguably it's a PHP bug),
        // an opt-in/out flag to restrict the format on import should be added
        // to DateField to get used here.  Until then, this will do.

        return new ($field->phpType)($string);
    }

    public function canImport(Field $field, string $format): bool
    {
        return in_array($field->phpType, [\DateTimeInterface::class, \DateTime::class, \DateTimeImmutable::class], true);
    }
}
