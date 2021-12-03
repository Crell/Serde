<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Deserializer;
use Crell\Serde\Field;

class EnumOnArrayPropertyReader extends EnumPropertyReader
{
    public function writeValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        if ($source[$field->serializedName] ?? null instanceof \UnitEnum) {
            return $source[$field->serializedName];
        }
        return parent::writeValue($deserializer, $field, $source);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->typeCategory->isEnum() && $format === 'array';
    }
}
