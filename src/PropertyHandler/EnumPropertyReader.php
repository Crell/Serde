<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;
use Crell\Serde\TypeCategory;

class EnumPropertyReader implements PropertyReader, PropertyWriter
{
    public function readValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $scalar = $value->value ?? $value->name;

        return match (true) {
            is_int($scalar) => $formatter->serializeInt($runningValue, $field, $scalar),
            is_string($scalar) => $formatter->serializeString($runningValue, $field, $scalar),
        };
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory->isEnum();
    }

    public function writeValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        // It's kind of amusing that both of these work, but they work.
        return match ($field->typeCategory) {
            TypeCategory::UnitEnum => (new \ReflectionEnum($field->phpType))->getCase($formatter->deserializeString($source, $field))->getValue(),
            TypeCategory::IntEnum => $field->phpType::from($formatter->deserializeInt($source, $field)),
            TypeCategory::StringEnum => $field->phpType::from($formatter->deserializeString($source, $field)),
        };
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->typeCategory->isEnum();
    }


}
