<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\AST\BooleanValue;
use Crell\Serde\AST\DateTimeValue;
use Crell\Serde\AST\DictionaryValue;
use Crell\Serde\AST\FloatValue;
use Crell\Serde\AST\IntegerValue;
use Crell\Serde\AST\SequenceValue;
use Crell\Serde\AST\StringValue;
use Crell\Serde\AST\StructValue;
use Crell\Serde\AST\Value;

class AstToArray
{
    public function do(Value $value): mixed
    {
        return match (get_class($value)) {
            BooleanValue::class, IntegerValue::class, FloatValue::class, StringValue::class => $value->value,
            DateTimeValue::class => ['timestamp' => $value->dateTime, 'timezone' => $value->dateTimeZone, 'immutable' => $value->immutable],
            SequenceValue::class => array_map([$this, 'do'], $value->values),
            DictionaryValue::class => $this->doDictionary($value),
            StructValue::class => [$value->type => array_map([$this, 'do'], $value->values)],
        };
    }

    protected function doDictionary(DictionaryValue $value): array
    {
        return array_combine(
            array_keys($value->values),
            array_map([$this, 'do'], $value->values),
        );
    }

    /*
    protected function doDateTime(DateTimeValue $value): \DateTimeInterface
    {
        return $value->immutable
            ? new \DateTimeImmutable($value->dateTime, new \DateTimeZone($value->dateTimeZone))
            : new \DateTime($value->dateTime, new \DateTimeZone($value->dateTimeZone));
    }
    */
}
