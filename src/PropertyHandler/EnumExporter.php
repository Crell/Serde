<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;

class EnumExporter implements Exporter, Importer
{
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $scalar = $value->value ?? $value->name;

        // PHPStan can't handle match() without a default.
        // @phpstan-ignore-next-line
        return match (true) {
            is_int($scalar) => $serializer->formatter->serializeInt($runningValue, $field, $scalar),
            is_string($scalar) => $serializer->formatter->serializeString($runningValue, $field, $scalar),
        };
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory->isEnum();
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        // It's kind of amusing that both of these work, but they work.
        $val = match ($field->typeCategory) {
            TypeCategory::UnitEnum => $deserializer->deformatter->deserializeString($source, $field),
            TypeCategory::IntEnum => $deserializer->deformatter->deserializeInt($source, $field),
            TypeCategory::StringEnum => $deserializer->deformatter->deserializeString($source, $field),
        };

        if ($val instanceof SerdeError) {
            return $val;
        }

        // It's kind of amusing that both of these work, but they work.
        return match ($field->typeCategory) {
            // The first line will only be called if $val is a string, but PHPStan thinks it could be an array.
            // @phpstan-ignore-next-line
            TypeCategory::UnitEnum => (new \ReflectionEnum($field->phpType))->getCase($val)->getValue(),
            TypeCategory::IntEnum, TypeCategory::StringEnum => $field->phpType::from($val),
        };
    }

    public function canImport(Field $field, string $format): bool
    {
        return $field->typeCategory->isEnum();
    }

}
