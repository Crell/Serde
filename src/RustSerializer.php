<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;

class RustSerializer
{
    protected ClassAnalyzer $analyzer;

    public function __construct()
    {
        $this->analyzer = new Analyzer();
    }

    public function serialize(object $o, string $format): string
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($o, ClassDef::class);

        $rObject = new \ReflectionObject($o);

        // @todo $format would get used here.
        $formatter = new JsonFormatter();

        $props = array_filter($objectMetadata->properties, $this->shouldSerialize($rObject, $o));

        $eachProp = function ($runningValue, Field $field) use ($formatter, $o) {
            $name = $this->mangle($field->name);
            return match ($field->phpType) {
                'int' => $formatter->serializeInt($runningValue, $name, $o->$name),
                'float' => $formatter->serializeFloat($runningValue, $name, $o->$name),
                'bool' => $formatter->serializeBool($runningValue, $name, $o->$name),
                'string' => $formatter->serializeString($runningValue, $name, $o->$name),
                'array' => $formatter->serializeArray($runningValue, $name, $o->$name),
                'object' => $formatter->serializeObject($runningValue, $name, $o->$name),
                default => throw new \RuntimeException('Cannot match ' . $field->phpType),
            };
        };

        $runningValue = array_reduce($props, $eachProp, $formatter->initialize());

        return $formatter->finalize($runningValue);
    }

    protected function shouldSerialize(\ReflectionObject $rObject, object $o): callable
    {
        return static fn (Field $field) =>
            $rObject->getProperty($field->phpName)->isInitialized($o)
            && !is_null($o->{$field->phpName});
    }

    public function deserialize(string $serialized, string $from, string $to): object
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($to, ClassDef::class);

        // $from would get used here.
        $formatter = new JsonFormatter();

        $props = [];

        $decoded = $formatter->deserializeInitialize($serialized);

        // Build up an array of properties that we can then assign all at once.
        foreach ($objectMetadata->properties as $field) {
            $name = $this->mangle($field->name);

            $props[$field->name] = match ($field->phpType) {
                'int' => $formatter->deserializeInt($decoded, $name),
                'float' => $formatter->deserializeFloat($decoded, $name),
                'bool' => $formatter->deserializeBool($decoded, $name),
                'string' => $formatter->deserializeString($decoded, $name),
                'array' => $formatter->deserializeArray($decoded, $name),
                'object' => $formatter->deserializeObject($decoded, $name),
                default => throw new \RuntimeException('Could not handle ' . $field->phpType),
            };
        }

        $rClass = new \ReflectionClass($to);
        $new = $rClass->newInstanceWithoutConstructor();

        // Get defaults from the constructor if necessary and possible.
        foreach ($rClass->getConstructor()?->getParameters() ?? [] as $param) {
            if ($props[$param->name] === SerdeError::Missing) {
                $props[$param->name] = $param->getDefaultValue();
            }
        }

        $populate = function (array $props) {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        };
        $populate->bindTo($new, $new)($props);
        return $new;

    }

    protected function mangle(string $name): string
    {
        return $name;
    }
}
