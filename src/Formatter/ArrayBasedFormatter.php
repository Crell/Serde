<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Field;

/**
 * Utility implementations for array-based formats.
 *
 * Formats that work by first converting the serialized format to/from an
 * array can use this trait to handle creating the array bits, then
 * implement the initialize/finalize logic specific for that format.
 */
trait ArrayBasedFormatter
{

    public function serializeInt(mixed $runningValue, Field $field, int $next): mixed
    {
        $runningValue[$field->serializedName()] = $next;
        return $runningValue;
    }

    public function serializeFloat(mixed $runningValue, Field $field, float $next): mixed
    {
        $runningValue[$field->serializedName()] = $next;
        return $runningValue;
    }

    public function serializeString(mixed $runningValue, Field $field, string $next): mixed
    {
        $runningValue[$field->serializedName()] = $next;
        return $runningValue;
    }

    public function serializeBool(mixed $runningValue, Field $field, bool $next): mixed
    {
        $runningValue[$field->serializedName()] = $next;
        return $runningValue;
    }

    public function serializeArray(mixed $runningValue, Field $field, array $next, callable $recursor): mixed
    {
        $name = $field->serializedName();
        foreach ($next as $k => $v) {
            $runningValue[$name][$k] = is_object($v) ? $recursor($v, []) : $v;
        }
        return $runningValue;
    }

    public function serializeDictionary(mixed $runningValue, Field $field, array $next, callable $recursor): mixed
    {
        $name = $field->serializedName();
        foreach ($next as $k => $v) {
            $runningValue[$name][$k] = match (true) {
                is_object($v) => $recursor($v, []),
                is_array($v) => $recursor($v, []),
                default => $v,
            };
        }
        return $runningValue;
    }
}
