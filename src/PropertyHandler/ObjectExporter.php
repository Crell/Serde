<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\Field;
use Crell\Serde\CollectionItem;
use Crell\Serde\Dict;
use Crell\Serde\InvalidFieldForFlattening;
use Crell\Serde\DeformatterResult;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeMap;
use function Crell\fp\pipe;
use function Crell\fp\reduce;
use function Crell\fp\reduceWithKeys;

class ObjectExporter implements Exporter
{
    /**
     * @param Serializer $serializer
     * @param Field $field
     * @param object $value
     * @param mixed $runningValue
     * @return mixed
     */
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        // This lets us read private values without messing with the Reflection API.
        // The object_vars business is to let us differentiate between a value set to null
        // and an uninitialized value, which in this rare case are meaningfully different.
        // @todo This may benefit from caching get_object_vars(), but that will be tricky.
        $propReader = (fn (string $prop): mixed
            => array_key_exists($prop, get_object_vars($this)) ? $this->$prop : DeformatterResult::Missing)->bindTo($value, $value);

        /** @var \Crell\Serde\Dict $dict */
        $dict = pipe(
            $serializer->propertiesFor($value::class),
            reduce(new Dict(), fn(Dict $dict, Field $f) => $this->flattenValue($dict, $f, $propReader, $serializer)),
        );

        if ($map = $serializer->typeMapper->typeMapForField($field)) {
            $f = Field::create(serializedName: $map->keyField(), phpType: 'string');
            // The type map field MUST come first so that streaming deformatters
            // can know their context.
            $dict->items = [new CollectionItem(field: $f, value: $map->findIdentifier($value::class)), ...$dict->items];
        }

        return $serializer->formatter->serializeObject($runningValue, $field, $dict, $serializer);
    }

    /**
     * If appropriate, flatten a compound field into discrete items.
     *
     * This method is called as a reducing function.
     *
     * @param Dict $dict
     *   The
     * @param Field $field
     *   Field definition.
     * @param callable $propReader
     *   The value reader bound to the current property.
     * @param Serializer $serializer
     *   The serializer context.
     * @return Dict
     *   The reducing value, with whatever appropriate additional items added.
     */
    protected function flattenValue(Dict $dict, Field $field, callable $propReader, Serializer $serializer): Dict
    {
        $value = $propReader($field->phpName);
        if ($value === DeformatterResult::Missing) {
            return $dict;
        }

        if ($field->omitIfNull && is_null($value)) {
            return $dict;
        }

        if (!$field->flatten) {
            return $dict->add(new CollectionItem(field: $field, value: $value));
        }

        if ($field->typeCategory === TypeCategory::Array) {
            // This really wants to be explicit partial application. :-(
            $c = fn (Dict $dict, $val, $key) => $this->reduceArrayElement($dict, $val, $key, $serializer->typeMapper->typeMapForField($field));
            return reduceWithKeys($dict, $c)($value);
        }

        if ($field->typeCategory === TypeCategory::Object) {
            if ($value === null) {
                return $dict;
            }
            $subPropReader = (fn (string $prop): mixed
                => array_key_exists($prop, get_object_vars($this)) ? $this->$prop : DeformatterResult::Missing)->bindTo($value, $value);
            // This really wants to be explicit partial application. :-(
            $c = fn (Dict $dict, Field $prop) => $this->reduceObjectProperty($dict, $prop, $subPropReader, $field, $serializer);
            $properties = $serializer->propertiesFor($value::class);
            $dict = reduce($dict, $c)($properties);
            if ($map = $serializer->typeMapper->typeMapForField($field)) {
                $f = Field::create(serializedName: $map->keyField(), phpType: 'string');
                // The type map field MUST come first so that streaming deformatters
                // can know their context.
                $dict->items = [new CollectionItem(field: $f, value: $map->findIdentifier($value::class)), ...$dict->items];
            }
            return $dict;
        }

        throw InvalidFieldForFlattening::create($field);
    }

    protected function reduceArrayElement(Dict $dict, mixed $val, string|int $key, ?TypeMap $map): Dict
    {
        $extra = [];
        if ($map) {
            $extra[$map->keyField()] = $map->findIdentifier($val::class);
        }
        $f = Field::create(serializedName: "$key", phpType: \get_debug_type($val), extraProperties: $extra);
        return $dict->add(new CollectionItem(field: $f, value: $val));
    }

    protected function reduceObjectProperty(Dict $dict, Field $prop, callable $subPropReader, Field $parentProperty, Serializer $serializer): Dict
    {
        // If there is a prefix provided by the parent field being flattened, we need to create a new, alternate
        // field definition for the property.  The serializedName field will be used only on the final property,
        // so while this will produce weird strings it along the way for the intermediary fields, that doesn't matter.
        if ($parentProperty->flattenPrefix) {
            /** @var Field $prop */
            $prop = $prop->with(
                serializedName: $parentProperty->flattenPrefix . $prop->serializedName,
                flattenPrefix: $parentProperty->flattenPrefix . $prop->flattenPrefix
            );
        }

        if ($prop->flatten) {
            return $this->flattenValue($dict, $prop, $subPropReader, $serializer);
        }

        return $dict->add(new CollectionItem(field: $prop, value: $subPropReader($prop->phpName)));
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object;
    }
}
