<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\AST\BooleanValue;
use Crell\Serde\AST\DictionaryValue;
use Crell\Serde\AST\FloatValue;
use Crell\Serde\AST\IntegerValue;
use Crell\Serde\AST\SequenceValue;
use Crell\Serde\AST\StringValue;
use Crell\Serde\AST\StructValue;
use Crell\Serde\AST\Value;

class AnnotatedArrayToAst
{
    public function do(array $array): Value
    {
        return $this->doArray($array);
    }

    protected function item(mixed $v): Value
    {
        return match (gettype($v)) {
            'integer' => new IntegerValue($v),
            'string' => new StringValue($v),
            'double' => new FloatValue($v),
            'boolean' => new BooleanValue($v),
            'array' => $this->doArray($v),
            default => throw new \RuntimeException("Did not match ". (string)$v),
        };
    }

    protected function doArray(array $array): Value
    {
        $type = key($array);
        return match (true) {
            is_string($type) && class_exists($type) => $this->doStruct($type, $array[$type]),
            $this->array_is_list($array) => new SequenceValue(array_map([$this, 'item'], $array)),
            default => $this->doDictionary($array),
        };
    }

    protected function doDictionary(array $values): DictionaryValue
    {
        return new DictionaryValue(array_combine(
            array_keys($values),
            array_map([$this, 'item'], $values),
        ));
    }

    protected function doStruct(string $type, $values): StructValue
    {
        return new StructValue($type, array_combine(
            array_keys($values),
            array_map([$this, 'item'], $values),
        ));
    }

    // This is native in PHP 8.1, so replace with that eventually.
    private function array_is_list(array $array): bool {
        $expectedKey = 0;
        foreach ($array as $i => $_) {
            if ($i !== $expectedKey) { return false; }
            $expectedKey++;
        }
        return true;
    }
}
