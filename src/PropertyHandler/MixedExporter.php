<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\CollectionItem;
use Crell\Serde\Deserializer;
use Crell\Serde\Dict;
use Crell\Serde\InvalidArrayKeyType;
use Crell\Serde\KeyType;
use Crell\Serde\Sequence;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;

class MixedExporter implements Importer, Exporter
{
    public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $type = \get_debug_type($value);

        if ($type === 'array') {
            if (array_is_list($value)) {
                return $serializer->formatter->serializeSequence($runningValue, $field, $this->arrayToSequence($value), $serializer);
            } else {
                $dict = $this->arrayToDict($value, $field);
                return $serializer->formatter->serializeDictionary($runningValue, $field, $dict, $serializer);
            }
        }

        return match ($type) {
            'int' => $serializer->formatter->serializeInt($runningValue, $field, $value),
            'float' => $serializer->formatter->serializeFloat($runningValue, $field, $value),
            'bool' => $serializer->formatter->serializeBool($runningValue, $field, $value),
            'string' => $serializer->formatter->serializeString($runningValue, $field, $value),
        };
    }

    /**
     * @param array<mixed> $value
     */
    protected function arrayToSequence(array $value): Sequence
    {
        $items = [];
        foreach ($value as $k => $v) {
            $f = Field::create(serializedName: "$k", phpType: \get_debug_type($v));
            $items[] = new CollectionItem(field: $f, value: $v);
        }
        return new Sequence($items);
    }

    /**
     * @param array<mixed, mixed> $value
     */
    protected function arrayToDict(array $value, Field $field): Dict
    {
        /** @var DictionaryField|null $typeField */
        $typeField = $field->typeField;

        $items = [];
        foreach ($value as $k => $v) {
            // Most $runningValue implementations will be an array.
            // Arrays in PHP force-cast an integer-string key to
            // an integer.  That means we cannot guarantee the type
            // of the key going out in the Exporter. The Formatter
            // will have to do so, if it cares. However, we can still
            // detect and reject string-in-int.
            if ($typeField?->keyType === KeyType::Int && \get_debug_type($k) === 'string') {
                // It's an int field, but the key is a string. That's a no-no.
                throw InvalidArrayKeyType::create($field, 'string');
            }
            $f = Field::create(serializedName: "$k", phpType: \get_debug_type($v));
            $items[] = new CollectionItem(field: $f, value: $v);
        }

        return new Dict($items);
    }

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        // This is normally a bad idea, as the $source should be opaque. In this
        // case, we're guaranteed that the $source is array-based, so we can introspect
        // it directly.
        return match (\get_debug_type($source[$field->serializedName])) {
            'int' => $deserializer->deformatter->deserializeInt($source, $field),
            'float' => $deserializer->deformatter->deserializeFloat($source, $field),
            'bool' => $deserializer->deformatter->deserializeBool($source, $field),
            'string' => $deserializer->deformatter->deserializeString($source, $field),
            'array' => $deserializer->deformatter->deserializeDictionary($source, $field, $deserializer),
        };
    }

    public function canExport(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Mixed;
    }

    public function canImport(Field $field, string $format): bool
    {
        // We can only import if we know that the $source will be an array so that it
        // can be introspected.  If it's not, then this class has no way to tell what
        // type to tell the Deformatter to read.
        return $field->typeCategory === TypeCategory::Mixed && in_array($format, ['json', 'yaml', 'array']);
    }
}
