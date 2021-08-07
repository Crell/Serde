<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use function json_encode;

class JsonSerde
{

    public function __construct(protected ClassAnalyzer $analyzer) {}

    public function serialize(object $subject): string
    {
        /** @var ClassDef $classDef */
        $classDef = $this->analyzer->analyze($subject, ClassDef::class);
        $fields = $classDef->properties;

        $data = $this->buildFieldValueMap($fields, $subject);

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    public function deserialize(string $json, string $class): object
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $this->decode($data, $class);
    }

    /**
     * @param Field[] $fields
     */
    protected function buildFieldValueMap(array $fields, object $object): array
    {
        // @todo I'm not sure how to make this nicely functional, since $rProp would
        // be needed in both the map and the filter portion.
        $rObject = new \ReflectionObject($object);

        $ret = [];
        foreach ($fields as $field) {
            $rProp = $rObject->getProperty($field->phpName);
            if (! $rProp->isInitialized($object)) {
                continue;
            }
            $value = $rProp->getValue($object);
            $ret[$field->name] = $value;
        }
        return $ret;
    }

    protected function decode(array $data, string $class): object
    {
        /** @var ClassDef $classDef */
        $classDef = $this->analyzer->analyze($class, ClassDef::class);
        $fields = $classDef->properties;

        $decoder = fn(array $data, string $class): object => $this->decode($data, $class);

        $populate = function(array $data) use ($fields, $decoder) {
            foreach ($fields as $name => $field) {
                $this->$name = match ($field->phpType) {
                    Field::TYPE_NOT_SPECIFIED, 'int', 'float', 'string', 'array', 'bool' => $data[$field->name] ?? $field->default,
                    \DateTime::class => new \DateTime($data[$name]['date'], timezone: new \DateTimeZone($data[$name]['timezone'])),
                    \DateTimeImmutable::class => new \DateTimeImmutable($data[$name]['date'], timezone: new \DateTimeZone($data[$name]['timezone'])),
                    default => $decoder($data[$name], $field->phpType),
                };
            }
        };

        $new = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        $populate->bindTo($new, $new)($data);
        return $new;
    }
}
