<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\CollectionItem;
use Crell\Serde\Deserializer;
use Crell\Serde\Dict;
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;

class NativeSerializeExporter implements Exporter, Importer
{
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $propValues = $value->__serialize();

        $dict = new Dict();

        foreach ($propValues as $k => $v) {
            $dict->items[] = new CollectionItem(
                field: Field::create(serializedName: "$k", phpType: \get_debug_type($v)),
                value: $v,
            );
        }

        if ($map = $serializer->typeMapper->typeMapForField($field)) {
            $f = Field::create(serializedName: $map->keyField(), phpType: 'string');
            // The type map field MUST come first so that streaming deformatters
            // can know their context.
            $dict->items = [new CollectionItem(field: $f, value: $map->findIdentifier($value::class)), ...$dict->items];
        }

        return $serializer->formatter->serializeDictionary($runningValue, $field, $dict, $serializer);
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object && method_exists($value, '__serialize');
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        // The data may not have any relation at all to the original object's
        // properties.  So deserialize as a basic dictionary instead.
        $dict = $deserializer->deformatter->deserializeDictionary($source, $field, $deserializer);

        if ($dict instanceof SerdeError) {
            return null;
        }

        $class = $deserializer->typeMapper->getTargetClass($field, $dict);

        if (is_null($class)) {
            return null;
        }

        // Make an empty instance of the target class.
        $rClass = new \ReflectionClass($class);
        $new = $rClass->newInstanceWithoutConstructor();

        // We wouldn't have gotten here unless this method is defined, but PHPStan
        // can't know that.
        // @phpstan-ignore-next-line
        $new->__unserialize($dict);

        return $new;
    }

    public function canImport(Field $field, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object && method_exists($field->phpType, '__unserialize');
    }
}
