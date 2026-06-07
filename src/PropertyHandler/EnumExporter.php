<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\CollectionItem;
use Crell\Serde\DeformatterResult;
use Crell\Serde\Deserializer;
use Crell\Serde\Dict;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeMismatch;
use ReflectionEnum;
use function Crell\fp\pipe;
use function Crell\fp\reduce;

class EnumExporter implements Exporter, Importer
{
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        if ($field->typeCategory !== TypeCategory::UnitEnum && $map = $serializer->typeMapper->typeMapForField($field)) {
            // This lets us read private values without messing with the Reflection API.
            // The object_vars business is to let us differentiate between a value set to null
            // and an uninitialized value, which in this rare case are meaningfully different.
            // @todo This may benefit from caching get_object_vars(), but that will be tricky.
            $propReader = (fn (string $prop): mixed
                => array_key_exists($prop, get_object_vars($this)) ? $this->$prop : DeformatterResult::Missing)->bindTo($value, $value);

            /** @var Dict $dict */
            $dict = pipe(
                $serializer->propertiesFor($value::class),
                reduce(new Dict(), fn(Dict $dict, Field $f) => $dict),
            );

            $f = Field::create(serializedName: $map->keyField(), phpType: 'string');
            // The type map field MUST come first so that streaming deformatters
            // can know their context.
            $dict->items = [
                new CollectionItem(field: $f, value: $map->findIdentifier($value::class)),
            ];

            $result = $serializer->formatter->serializeObject($runningValue, $field, $dict, $serializer);
            $result[0]['value'] = $value->value;

            return $result;
        }

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
        if ($field->typeCategory !== TypeCategory::UnitEnum && $deserializer->typeMapper->typeMapForField($field) !== null) {
            $source = [$source[0]['value']];
        }

        // It's kind of amusing that both of these work, but they work.
        $val = match ($field->typeCategory) {
            TypeCategory::UnitEnum, TypeCategory::StringEnum => $deserializer->deformatter->deserializeString($source, $field),
            TypeCategory::IntEnum => $deserializer->deformatter->deserializeInt($source, $field),
            default => throw TypeMismatch::create($field->phpName, $field->phpType, get_debug_type($source)),
        };

        if ($field->nullable && $val === null) {
            return null;
        }

        if ($val instanceof DeformatterResult) {
            return $val;
        }

        // It's kind of amusing that both of these work, but they work.
        return match ($field->typeCategory) {
            // The first line will only be called if $val is a string, but PHPStan thinks it could be an array.
            // @phpstan-ignore-next-line
            TypeCategory::UnitEnum => (new ReflectionEnum($field->phpType))->getCase($val)->getValue(),
            TypeCategory::IntEnum, TypeCategory::StringEnum => $field->phpType::from($val),
        };
    }

    public function canImport(Field $field, string $format): bool
    {
        return $field->typeCategory->isEnum();
    }

}
