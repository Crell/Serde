<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\AST\BooleanValue;
use Crell\Serde\AST\FloatValue;
use Crell\Serde\AST\IntegerValue;
use Crell\Serde\AST\StringValue;
use Crell\Serde\AST\StructValue;
use Crell\Serde\AST\Value;

class ObjectDecoder
{
    public function __construct(protected ClassAnalyzer $analyzer) {}

    public function decode(object $object): Value
    {
        /** @var ClassDef $classDef */
        $classDef = $this->analyzer->analyze($object, ClassDef::class);

        $data = $this->buildFieldValueMap($classDef, $object);

        return $data;
    }

    protected function buildFieldValueMap(ClassDef $classDef, object $object): StructValue
    {
        // @todo I'm not sure how to make this nicely functional, since $rProp would
        // be needed in both the map and the filter portion.
        $rObject = new \ReflectionObject($object);

        $fields = $classDef->properties;

        $ret = new StructValue($classDef->fullName);

        foreach ($fields as $field) {
            $rProp = $rObject->getProperty($field->phpName);
            if (! $rProp->isInitialized($object)) {
                continue;
            }
            $value = $rProp->getValue($object);

            $ret->values[$field->name] = match ($field->phpType) {
                'int' => new IntegerValue($value),
                'float' => new FloatValue($value),
                'string' => new StringValue($value),
                'bool' => new BooleanValue($value),
                'object' => $this->decode($value),
            };
        }

        return $ret;
    }
}
