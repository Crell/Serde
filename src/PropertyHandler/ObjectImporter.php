<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;
use Crell\Serde\Formatter\SupportsCollecting;
use Crell\Serde\InvalidArrayKeyType;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeCategory;

class ObjectImporter implements Importer
{
    protected readonly \Closure $populator;
    protected readonly \Closure $methodCaller;

    public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
    {
        // Get the raw data as an array from the source.
        $dict = $deserializer->deformatter->deserializeObject($source, $field, $deserializer);

        if ($dict instanceof SerdeError) {
            return null;
        }

        $class = $deserializer->typeMapper->getTargetClass($field, $dict);

        if (is_null($class)) {
            return null;
        }

        [$object, $remaining] = $this->populateObject($dict, $class, $deserializer);
        return $object;
    }

    /**
     * @param array<string, mixed> $dict
     * @param class-string $class
     * @param Deserializer $deserializer
     * @return array{object, mixed[]}
     */
    protected function populateObject(array $dict, string $class, Deserializer $deserializer): array
    {
        $classDef = $deserializer->analyzer->analyze($class, ClassSettings::class, scopes: $deserializer->scopes);

        $props = [];
        $usedNames = [];
        $collectingArray = null;
        /** @var Field[] $collectingObjects */
        $collectingObjects = [];

        foreach ($classDef->properties as $propField) {
            $usedNames[] = $propField->serializedName;
            if ($propField->flatten && $propField->typeCategory === TypeCategory::Array) {
                $collectingArray = $propField;
            } elseif ($propField->flatten && $propField->typeCategory === TypeCategory::Object) {
                $collectingObjects[] = $propField;
            } else {
                $value = $dict[$propField->serializedName] ?? SerdeError::Missing;
                if ($value !== SerdeError::Missing && !$propField->validate($value)) {
                    throw InvalidArrayKeyType::create($propField, 'invalid');
                }
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
            if ($targetClass = $deserializer->typeMapper->getTargetClass($collectingField, $dict)) {
                [$object, $remaining] = $this->populateObject($remaining, $targetClass, $deserializer);
                $props[$collectingField->phpName] = $object;
                if ($map = $deserializer->typeMapper->typeMapForField($collectingField)) {
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

    /**
     * Intantiates an object based on the provided data.
     *
     * @param class-string $class
     *   The class of object to create.
     * @param array<string, mixed> $props
     *   An associative array of properties to inject into the new object.
     * @param string[] $callbacks
     *   An array of method names to invoke after the properties are populated.
     * @return object
     *   The populated object.
     * @throws \ReflectionException
     */
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

        // Call the populator with the scope of the new object.
        $this->populator->call($new, $props);

        // Invoke any post-load callbacks, even if they're private.
        $invoker = $this->methodCaller->bindTo($new, $new);
        // bindTo() technically could return null on error, but there's no
        // indication of when that would happen. So this is really just to
        // keep static analyzers happy.
        if ($invoker) {
            foreach ($callbacks as $fn) {
                $invoker($fn);
            }
        }

        return $new;
    }

    public function canImport(Field $field, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object;
    }
}
