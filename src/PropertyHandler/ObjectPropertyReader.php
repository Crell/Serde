<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\CollectionItem;
use Crell\Serde\Deserializer;
use Crell\Serde\Dict;
use Crell\Serde\Field;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\Formatter\SupportsCollecting;
use Crell\Serde\NoTypeMapDefinedForKey;
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeMap;
use function Crell\fp\pipe;
use function Crell\fp\reduce;
use function Crell\fp\reduceWithKeys;

class ObjectPropertyReader implements PropertyWriter, PropertyReader
{
    protected readonly \Closure $populator;
    protected readonly \Closure $methodCaller;

    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    /**
     * @param Serializer $serializer
     * @param Field $field
     * @param object $value
     * @param mixed $runningValue
     * @return mixed
     */
    public function readValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
    {
        // This lets us read private values without messing with the Reflection API.
        $propReader = (fn (string $prop): mixed => $this->$prop ?? null)->bindTo($value, $value);

        /** @var \Crell\Serde\Dict $dict */
        $dict = pipe(
            $this->analyzer->analyze($value, ClassDef::class)->properties,
            reduce(new Dict(), fn(Dict $dict, Field $f) => $this->flattenValue($dict, $f, $propReader)),
        );

        if ($map = $this->typeMap($field)) {
            $f = Field::create(serializedName: $map->keyField(), phpType: 'string');
            // The type map field MUST come first so that streaming deformatters
            // can know their context.
            $dict->items = [new CollectionItem(field: $f, value: $map->findIdentifier($value::class)), ...$dict->items];
        }

        return $serializer->formatter->serializeDictionary($runningValue, $field, $dict, $serializer);
    }

    protected function flattenValue(Dict $dict, Field $field, callable $propReader): Dict
    {
        $value = $propReader($field->phpName);
        if ($value === null) {
            return $dict;
        }

        if (!$field->flatten) {
            $dict->items[] = new CollectionItem(field: $field, value: $value);
            return $dict;
        }

        if ($field->typeCategory === TypeCategory::Array) {
            // This really wants to be explicit partial application. :-(
            $c = fn (Dict $dict, $val, $key) => $this->reduceArrayElement($dict, $val, $key, $this->typeMap($field));
            return reduceWithKeys($dict, $c)($value);
        }

        if ($field->typeCategory === TypeCategory::Object) {
            $subPropReader = (fn (string $prop): mixed => $this->$prop ?? null)->bindTo($value, $value);
            // This really wants to be explicit partial application. :-(
            $c = fn (Dict $dict, Field $prop) => $this->reduceObjectProperty($dict, $prop, $subPropReader);
            $properties = $this->analyzer->analyze($value::class, ClassDef::class)->properties;
            $dict = reduce($dict, $c)($properties);
            if ($map = $this->typeMap($field)) {
                $f = Field::create(serializedName: $map->keyField(), phpType: 'string');
                // The type map field MUST come first so that streaming deformatters
                // can know their context.
                $dict->items = [new CollectionItem(field: $f, value: $map->findIdentifier($value::class)), ...$dict->items];
            }
            return $dict;
        }

        // @todo Better exception.
        throw new \RuntimeException('Invalid flattening field type');
    }

    protected function reduceArrayElement(Dict $dict, $val, $key, ?TypeMap $map): Dict
    {
        $extra = [];
        if ($map) {
            $extra[$map->keyField()] = $map->findIdentifier($val::class);
        }
        $f = Field::create(serializedName: "$key", phpType: \get_debug_type($val), extraProperties: $extra);
        $dict->items[] = new CollectionItem(field: $f, value: $val);
        return $dict;
    }

    protected function reduceObjectProperty(Dict $dict, Field $prop, callable $subPropReader): Dict
    {
        if ($prop->flatten) {
            return $this->flattenValue($dict, $prop, $subPropReader);
        }

        $dict->items[] = new CollectionItem(field: $prop, value: $subPropReader($prop->phpName));
        return $dict;
    }

    protected function typeMap(Field $field): ?TypeMap
    {
        if ($field->typeMap) {
            return $field->typeMap;
        }

        if (class_exists($field->phpType) || interface_exists($field->phpType)) {
            return $this->analyzer->analyze($field->phpType, ClassDef::class)?->typeMap;
        }

        return null;
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object;
    }

    public function writeValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        // Get the raw data as an array from the source.
        $dict = $deserializer->deformatter->deserializeObject($source, $field, $deserializer->deserialize(...), $this->typeMap($field));

        if ($dict === SerdeError::Missing) {
            return null;
        }

        $class = $this->getTargetClass($field, $dict);

        [$object, $remaining] = $this->populateObject($dict, $class, $deserializer);
        return $object;
    }

    /**
     * @param array $dict
     * @param string $class
     * @param Deserializer $deserializer
     * @param TypeMap|null $map
     * @return [object, array]
     */
    protected function populateObject(array $dict, string $class, Deserializer $deserializer): array
    {
        /** @var ClassDef $classDef */
        $classDef = $this->analyzer->analyze($class, ClassDef::class);

        // Get the list of properties on the target class, taking
        // type maps into account.
        $properties = $classDef->properties;

        $props = [];
        $usedNames = [];
        $collectingArray = null;
        /** @var Field[] $collectingObjects */
        $collectingObjects = [];

        foreach ($properties as $propField) {
            $usedNames[] = $propField->serializedName;
            if ($propField->flatten && $propField->typeCategory === TypeCategory::Array) {
                $collectingArray = $propField;
            } elseif ($propField->flatten && $propField->typeCategory === TypeCategory::Object) {
                $collectingObjects[] = $propField;
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

        // We don't care about collecting, so just stop now.
        // If we later add support for erroring on extra unhandled fields,
        // this is where that logic would live.
        if (! $deserializer->deformatter instanceof SupportsCollecting) {
            return [$this->createObject($class, $props, $classDef->postLoadCallacks), []];
        }

        $remaining = $dict;
        foreach ($collectingObjects as $collectingField) {
            $remaining = $deserializer->deformatter->getRemainingData($remaining, $usedNames);
            // It's possible there will be a class map but no mapping field in
            // the data. In that case, either set a default or just ignore the field.
            if ($targetClass = $this->getTargetClass($collectingField, $dict)) {
                [$object, $remaining] = $this->populateObject($remaining, $targetClass, $deserializer);
                $props[$collectingField->phpName] = $object;
                if ($map = $this->typeMap($collectingField)) {
                    $usedNames[] = $map->keyField();
                }
            } elseif ($collectingField->shouldUseDefault) {
                $props[$collectingField->phpName] = $collectingField->defaultValue;
            }
        }

        $remaining = $deserializer->deformatter->getRemainingData($remaining, $usedNames);
        if ($collectingArray) {
            $props[$collectingArray->phpName] = $remaining;
            $remaining = [];
        }

        // If we later add support for erroring on extra unhandled fields,
        // this is where that logic would live.

        return [$this->createObject($class, $props, $classDef->postLoadCallacks), $remaining];
    }

    protected function createObject(string $class, array $props, array $callbacks): object
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

        // Cache the method invoker  same way.
        $this->methodCaller ??= fn(string $fn) => $this->$fn();

        // Bind the populator to the object to bypass visibility rules,
        // then invoke it on the object to populate it.
        $this->populator->bindTo($new, $new)($props);

        // Invoke any post-load callbacks, even if they're private.
        $invoker = $this->methodCaller->bindTo($new, $new);
        foreach ($callbacks as $fn) {
            $invoker($fn);
        }

        return $new;
    }

    protected function getTargetClass(Field $field, array $dict): ?string
    {
        if (!$map = $this->typeMap($field)) {
            return $field->phpType;
        }

        if (! $key = ($dict[$map->keyField()] ?? null)) {
            return null;
        }

        if (!$class = $map->findClass($key)) {
            throw NoTypeMapDefinedForKey::create($key, $field->phpName ?? $field->phpType);
        }

        return $class;
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object;
    }
}
