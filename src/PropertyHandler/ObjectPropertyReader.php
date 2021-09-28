<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeCategory;
use function Crell\fp\afilter;
use function Crell\fp\keyedMap;
use function Crell\fp\pipe;

class ObjectPropertyReader implements PropertyWriter, PropertyReader
{
    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    /**
     * @param JsonFormatter $formatter
     * @param callable $recursor
     * @param Field $field
     * @param object $value
     * @param mixed $runningValue
     * @return mixed
     */
    public function readValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($value, ClassDef::class);

        // This lets us read private values without messing with the Reflection API.
        $propReader = (fn (string $prop) => $this->$prop)->bindTo($value, $value);

        $dict = pipe(
            $objectMetadata->properties,
            afilter($this->shouldSerialize(new \ReflectionObject($value), $value)),
            keyedMap(
                values: static fn ($i, Field $field) => $propReader($field->phpName),
                keys: static fn ($i, Field $field) => $field->serializedName(),
            ),
        );

        if ($field->typeMap) {
            $dict[$field->typeMap->keyField()] = $field->typeMap->findIdentifier($value::class);
        }

        return $formatter->serializeDictionary($runningValue, $field, $dict, $recursor);
    }

    protected function shouldSerialize(\ReflectionObject $rObject, object $object): callable
    {
        // This lets us read private values without messing with the Reflection API.
        $propReader = (fn (string $prop) => $this->$prop)->bindTo($object, $object);

        // @todo Do we serialize nulls or no? Right now we don't.
        return static fn (Field $field): bool =>
            $rObject->getProperty($field->phpName)->isInitialized($object)
            && !is_null($propReader($field->phpName));
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object;
    }

    public function writeValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        $dict = $formatter->deserializeDictionary($source, $field, $recursor);

        if ($dict === SerdeError::Missing) {
            return null;
        }

        $class = $field->phpType;
        if ($field->typeMap) {
            $keyField = $field->typeMap->keyField();
            $class = $field->typeMap->findClass($dict[$keyField]);
            unset($dict[$keyField]);
        }

        return $recursor($dict, $class);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object;
    }
}
