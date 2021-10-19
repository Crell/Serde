<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\CollectionItem;
use Crell\Serde\Dict;
use Crell\Serde\Field;
use Crell\Serde\Sequence;

/**
 * Utility implementations for array-based formats.
 *
 * Formats that work by first converting the serialized format to/from an
 * array can use this trait to handle creating the array bits, then
 * implement the initialize/finalize logic specific for that format.
 */
trait ArrayBasedFormatter
{
    public function initialField(string $type): Field
    {
        return Field::create('root', $type);
    }

    public function serializeInt(mixed $runningValue, Field $field, int $next): array
    {
        $runningValue[$field->serializedName] = $next;
        return $runningValue;
    }

    public function serializeFloat(mixed $runningValue, Field $field, float $next): array
    {
        $runningValue[$field->serializedName] = $next;
        return $runningValue;
    }

    public function serializeString(mixed $runningValue, Field $field, string $next): array
    {
        $runningValue[$field->serializedName] = $next;
        return $runningValue;
    }

    public function serializeBool(mixed $runningValue, Field $field, bool $next): array
    {
        $runningValue[$field->serializedName] = $next;
        return $runningValue;
    }

    public function serializeSequence(mixed $runningValue, Field $field, Sequence $next, callable $recursor): array
    {
        $name = $field->serializedName;
        $runningValue[$name] = [];
        /** @var CollectionItem $item */
        foreach ($next->items as $item) {
            $runningValue[$name] = [...$runningValue[$name], ...$recursor($item->value, [], $item->field)];
        }
        $runningValue[$name] = array_values($runningValue[$name]);
        return $runningValue;
    }

    public function serializeDictionary(mixed $runningValue, Field $field, Dict $next, callable $recursor): array
    {
        $name = $field->serializedName;
        $runningValue[$name] = [];
        /** @var CollectionItem $item */
        foreach ($next->items as $item) {
            $runningValue[$name] = [...$runningValue[$name], ...$recursor($item->value, [], $item->field)];
        }
        return $runningValue;
    }
}
