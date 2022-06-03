<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\Field;
use Crell\Serde\CollectionItem;
use Crell\Serde\Deserializer;
use Crell\Serde\Dict;
use Crell\Serde\Sequence;
use Crell\Serde\Serializer;

/**
 * Utility implementations for array-based formats.
 *
 * Formats that work by first converting the serialized format to/from an
 * array can use this trait to handle creating the array bits, then
 * implement the initialize/finalize logic specific for that format.
 */
trait ArrayBasedFormatter
{
    public function rootField(Serializer|Deserializer $serializer, string $type): Field
    {
        return Field::create('root', $type);
    }

    /**
     * @param array<string, mixed> $runningValue
     * @param Field $field
     * @param int $next
     * @return array<string, mixed>
     */
    public function serializeInt(mixed $runningValue, Field $field, int $next): array
    {
        $runningValue[$field->serializedName] = $next;
        return $runningValue;
    }

    /**
     * @param array<string, mixed> $runningValue
     * @param Field $field
     * @param float $next
     * @return array<string, mixed>
     */
    public function serializeFloat(mixed $runningValue, Field $field, float $next): array
    {
        $runningValue[$field->serializedName] = $next;
        return $runningValue;
    }

    /**
     * @param array<string, mixed> $runningValue
     * @param Field $field
     * @param string $next
     * @return array<string, mixed>
     */
    public function serializeString(mixed $runningValue, Field $field, string $next): array
    {
        $runningValue[$field->serializedName] = $next;
        return $runningValue;
    }

    /**
     * @param array<string, mixed> $runningValue
     * @param Field $field
     * @param bool $next
     * @return array<string, mixed>
     */
    public function serializeBool(mixed $runningValue, Field $field, bool $next): array
    {
        $runningValue[$field->serializedName] = $next;
        return $runningValue;
    }

    /**
     * @param array<string, mixed> $runningValue
     * @param Field $field
     * @param Sequence $next
     * @return array<string, mixed>
     */
    public function serializeSequence(mixed $runningValue, Field $field, Sequence $next, Serializer $serializer): array
    {
        $name = $field->serializedName;
        $add = [];
        /** @var CollectionItem $item */
        foreach ($next->items as $item) {
            $add = [...$add, ...$serializer->serialize($item->value, [], $item->field)];
        }
        $runningValue[$name] = array_values($add);
        return $runningValue;
    }

    /**
     * @param array<string, mixed> $runningValue
     * @param Field $field
     * @param Dict $next
     * @return array<string, mixed>
     */
    public function serializeDictionary(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): array
    {
        $name = $field->serializedName;
        $add = [];
        /** @var CollectionItem $item */
        foreach ($next->items as $item) {
            $add += $serializer->serialize($item->value, [], $item->field);
        }
        $runningValue[$name] = $add;
        foreach ($field->extraProperties as $k => $v) {
            $runningValue[$name][$k] = $v;
        }
        return $runningValue;
    }

    /**
     * @param array<string, mixed> $runningValue
     * @param Field $field
     * @param Dict $next
     * @return array<string, mixed>
     */
    public function serializeObject(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): array
    {
        return $this->serializeDictionary($runningValue, $field, $next, $serializer);
    }
}
