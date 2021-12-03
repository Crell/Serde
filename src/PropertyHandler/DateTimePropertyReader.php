<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Deserializer;
use Crell\Serde\Field;
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;

class DateTimePropertyReader implements PropertyReader, PropertyWriter
{
    /**
     * @param Serializer $serializer
     * @param Field $field
     * @param \DateTimeInterface $value
     * @param mixed $runningValue
     * @return mixed
     */
    public function readValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $string = $value->format(\DateTimeInterface::RFC3339_EXTENDED);
        return $serializer->formatter->serializeString($runningValue, $field, $string);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $value instanceof \DateTimeInterface;
    }

    public function writeValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        $string = $deserializer->deformatter->deserializeString($source, $field);

        if ($string === SerdeError::Missing) {
            return null;
        }

        return new ($field->phpType)($string);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return in_array($field->phpType, [\DateTimeInterface::class, \DateTime::class, \DateTimeImmutable::class]);
    }
}
