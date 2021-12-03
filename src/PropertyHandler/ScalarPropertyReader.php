<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

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

    public function writeValue(Deformatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        return match ($field->phpType) {
            'int' => $formatter->deserializeInt($source, $field),
            'float' => $formatter->deserializeFloat($source, $field),
            'bool' => $formatter->deserializeBool($source, $field),
            'string' => $formatter->deserializeString($source, $field),
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
