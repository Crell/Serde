<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\Field;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeMapper;
use function Crell\fp\afilter;
use function Crell\fp\pipe;
use function Crell\fp\reduce;

class ObjectPropertyReader implements PropertyWriter, PropertyReader
{
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
        $propReader = (fn (string $prop) => $this->$prop)->bindTo($value, $value);

        $dict = pipe(
            $objectMetadata->properties,
            afilter($this->shouldSerialize(new \ReflectionObject($value), $value)),
            reduce([], fn(array $dict, Field $f) => $this->flattenValue($dict, $f, $propReader)),
        );

        if ($map = $this->typeMap($field)) {
            $dict[$map->keyField()] = $map->findIdentifier($value::class);
        }

        return $formatter->serializeDictionary($runningValue, $field, $dict, $recursor);
    }

    protected function flattenValue(array $dict, Field $field, callable $propReader): array
    {
        // @todo Figure out if we care about flattening/collecting objects.
        if ($field->flatten && $field->phpType === 'array') {
            foreach ($propReader($field->phpName) as $k => $v) {
                $dict[$k] = $v;
            }
        } else {
            $dict[$field->serializedName] = $propReader($field->phpName);
        }

        return $dict;
    }

    protected function typeMap(Field $field): ?TypeMapper
    {
        return $field->typeMap;
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

    public function writeValue(Deformatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        $dict = $formatter->deserializeDictionary($source, $field, $recursor);

        if ($dict === SerdeError::Missing) {
            return null;
        }

        $class = $field->phpType;
        if ($map = $this->typeMap($field)) {
            $keyField = $map->keyField();
            $class = $map->findClass($dict[$keyField]);
            unset($dict[$keyField]);
        }

        return $recursor($dict, $class);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return $field->typeCategory === TypeCategory::Object;
    }
}
