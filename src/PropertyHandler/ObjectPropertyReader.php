<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\CollectionItem;
use Crell\Serde\Dict;
use Crell\Serde\Field;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\Formatter\SupportsCollecting;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeMapper;
use function Crell\fp\pipe;
use function Crell\fp\reduce;

class ObjectPropertyReader implements PropertyWriter, PropertyReader
{
    protected \Closure $populator;

    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    /**
     * @param Formatter $formatter
     * @param callable $recursor
     * @param Field $field
     * @param object $value
     * @param mixed $runningValue
     * @return mixed
     */
    public function readValue(Formatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($value, ClassDef::class);

        // This lets us read private values without messing with the Reflection API.
        $propReader = (fn (string $prop): mixed => $this->$prop ?? null)->bindTo($value, $value);

        /** @var \Crell\Serde\Dict $dict */
        $dict = pipe(
            $objectMetadata->properties,
            reduce(new Dict(), fn(Dict $dict, Field $f) => $this->flattenValue($dict, $f, $propReader)),
        );

        if ($map = $this->typeMap($field)) {
            $f = Field::create(serializedName: $map->keyField(), phpType: 'string');
            // The type map field MUST come first so that streaming deformatters
            // can know their context.
            $dict->items = [new CollectionItem(field: $f, value: $map->findIdentifier($value::class)), ...$dict->items];
        }

        return $formatter->serializeDictionary($runningValue, $field, $dict, $recursor);
    }

    protected function flattenValue(Dict $dict, Field $field, callable $propReader): \Crell\Serde\Dict
    {
        $value = $propReader($field->phpName);
        if ($value === null) {
            return $dict;
        }

        if ($field->flatten) {
            $dict->items = match ($field->typeCategory) {
                TypeCategory::Array => [...$dict->items, ...$this->flattenArray($field, $value)],
                TypeCategory::Object => [...$dict->items, ...$this->flattenObject($field, $value)],
                // @todo Better exception.
                default => throw new \RuntimeException('Invalid flattening field type'),
            };
        } else {
            $dict->items[] = new CollectionItem(field: $field, value: $value);
        }

        return $dict;
    }

    protected function flattenArray(Field $field, array $arrayValue): array
    {
        $items = [];
        foreach ($arrayValue as $k => $v) {
            $extra = [];
            if ($map = $this->typeMap($field)) {
                $extra[$map->keyField()] = $map->findIdentifier($v::class);
            }
            $f = Field::create(serializedName: "$k", phpType: \get_debug_type($v), extraProperties: $extra);
            $items[] = new CollectionItem(field: $f, value: $v);
        }
        return $items;
    }

    protected function flattenObject(Field $field, object $value): array
    {
        $subPropReader = (fn (string $prop): mixed => $this->$prop ?? null)->bindTo($value, $value);

        // PHPStorm will complain about this, because it's stub file is out of date
        // and has the wrong argument names. It works.
        return array_map(
            callback: static fn (Field $prop) => new CollectionItem(field: $prop, value: $subPropReader($prop->phpName)),
            array: $this->analyzer->analyze($field->phpType, ClassDef::class)->properties,
        );
    }

    protected function typeMap(Field $field): ?TypeMapper
    {
        return $field->typeMap;
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object;
    }

    public function writeValue(Deformatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        // Get the raw data as an array from the source.
        $dict = $formatter->deserializeObject($source, $field, $recursor, $this->typeMap($field));

        if ($dict === SerdeError::Missing) {
            return null;
        }

        $class = $this->getTargetClass($field, $dict);

        // Get the list of properties on the target class, taking
        // type maps into account.
        $properties = $this->analyzer->analyze($class, ClassDef::class)->properties;

        $props = [];
        $usedNames = [];
        $collectingField = null;

        foreach ($properties as $propField) {
            $usedNames[] = $propField->serializedName;
            if ($propField->flatten) {
                $collectingField = $propField;
            } else {
                $value = $dict[$propField->serializedName] ?? SerdeError::Missing;
                if ($value === SerdeError::Missing) {
                    if ($propField->shouldUseDefault) {
                        $props[$propField->phpName] = $propField->defaultValue;
                    }
                } else {
                    $props[$propField->phpName] = $value;
                }
            }
        }

        if ($collectingField && $formatter instanceof SupportsCollecting) {
            $remaining = $formatter->getRemainingData($dict, $usedNames);
            $props[$collectingField->phpName] = match ($collectingField->typeCategory) {
                TypeCategory::Array => $remaining,
                TypeCategory::Object => $this->createObject($collectingField->phpType, $remaining),
                // @todo Better exception.
                default => throw new \RuntimeException('Invalid flattening field type'),
            };
        }

        // @todo What should happen if something is still set to Missing?

        return $this->createObject($class, $props);
    }

    protected function createObject(string $class, array $props): object
    {
        // Make an empty instance of the target class.
        $rClass = new \ReflectionClass($class);
        $new = $rClass->newInstanceWithoutConstructor();

        // Cache the populator template since it will be rebound
        // for every object. Micro-optimization.
        $this->populator ??= function (array $props) {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        };

        // Bind the populator to the object to bypass visibility rules,
        // then invoke it on the object to populate it.
        $this->populator->bindTo($new, $new)($props);
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
        return $field->typeCategory === TypeCategory::Object;
    }
}
