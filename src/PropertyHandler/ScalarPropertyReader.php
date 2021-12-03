<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Deserializer;
use Crell\Serde\Field;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;

class ScalarPropertyReader implements PropertyReader, PropertyWriter
{
    public function readValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        return match ($field->phpType) {
            'int' => $serializer->formatter->serializeInt($runningValue, $field, $value),
            'float' => $serializer->formatter->serializeFloat($runningValue, $field, $value),
            'bool' => $serializer->formatter->serializeBool($runningValue, $field, $value),
            'string' => $serializer->formatter->serializeString($runningValue, $field, $value),
        };
    }

    public function writeValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        return match ($field->phpType) {
            'int' => $deserializer->deformatter->deserializeInt($source, $field),
            'float' => $deserializer->deformatter->deserializeFloat($source, $field),
            'bool' => $deserializer->deformatter->deserializeBool($source, $field),
            'string' => $deserializer->deformatter->deserializeString($source, $field),
        };
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Scalar;
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Scalar;
    }
}
