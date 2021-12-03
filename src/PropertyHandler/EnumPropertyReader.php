<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Deserializer;
use Crell\Serde\Field;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;

class EnumPropertyReader implements PropertyReader, PropertyWriter
{
    public function readValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $scalar = $value->value ?? $value->name;

        return match (true) {
            is_int($scalar) => $serializer->formatter->serializeInt($runningValue, $field, $scalar),
            is_string($scalar) => $serializer->formatter->serializeString($runningValue, $field, $scalar),
        };
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory->isEnum();
    }

    public function writeValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        // It's kind of amusing that both of these work, but they work.
        $val = match ($field->typeCategory) {
            TypeCategory::UnitEnum => $deserializer->deformatter->deserializeString($source, $field),
            TypeCategory::IntEnum => $deserializer->deformatter->deserializeInt($source, $field),
            TypeCategory::StringEnum => $deserializer->deformatter->deserializeString($source, $field),
        };

        if ($val === SerdeError::Missing) {
            return $val;
        }

        // It's kind of amusing that both of these work, but they work.
        return match ($field->typeCategory) {
            TypeCategory::UnitEnum => (new \ReflectionEnum($field->phpType))->getCase($val)->getValue(),
            TypeCategory::IntEnum, TypeCategory::StringEnum => $field->phpType::from($val),
        };
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->typeCategory->isEnum();
    }

}
