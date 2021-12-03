<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\CollectionItem;
use Crell\Serde\Dict;
use Crell\Serde\Field;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeMap;

class NativeSerializePropertyReader implements PropertyReader, PropertyWriter
{
    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    public function readValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $propValues = $value->__serialize();

        $dict = new Dict();

        foreach ($propValues as $k => $v) {
            $dict->items[] = new CollectionItem(
                field: Field::create(serializedName: "$k", phpType: \get_debug_type($v)),
                value: $v,
            );
        }

        if ($map = $this->typeMap($field)) {
            $f = Field::create(serializedName: $map->keyField(), phpType: 'string');
            // The type map field MUST come first so that streaming deformatters
            // can know their context.
            $dict->items = [new CollectionItem(field: $f, value: $map->findIdentifier($value::class)), ...$dict->items];
        }

        return $serializer->formatter->serializeDictionary($runningValue, $field, $dict, $serializer->serialize(...));
    }

    protected function typeMap(Field $field): ?TypeMap
    {
        return $field->typeMap;
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object && method_exists($value, '__serialize');
    }

    public function writeValue(Deformatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        // The data may not have any relation at all to the original object's
        // properties.  So deserialize as a basic dictionary instead.
        $dict = $formatter->deserializeDictionary($source, $field, $recursor);

        if ($dict === SerdeError::Missing) {
            return null;
        }

        $class = $this->getTargetClass($field, $dict);

        // Make an empty instance of the target class.
        $rClass = new \ReflectionClass($class);
        $new = $rClass->newInstanceWithoutConstructor();

        $new->__unserialize($dict);

        return $new;
    }

    protected function getTargetClass(Field $field, array $dict): string
    {
        if ($map = $this->typeMap($field)) {
            return $map->findClass($dict[$map->keyField()]);
        }
        return $field->phpType;
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object && method_exists($field->phpType, '__unserialize');
    }
}
