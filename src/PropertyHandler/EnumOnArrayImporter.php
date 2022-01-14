<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;

class EnumOnArrayImporter extends EnumExporter
{
    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        if (($source[$field->serializedName] ?? null) instanceof \UnitEnum) {
            return $source[$field->serializedName];
        }
        return parent::importValue($deserializer, $field, $source);
    }

    public function canImport(Field $field, string $format): bool
    {
        return $field->typeCategory->isEnum() && $format === 'array';
    }
}
