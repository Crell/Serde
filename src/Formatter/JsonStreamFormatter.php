<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use Crell\Serde\CollectionItem;
use Crell\Serde\Dict;
use Crell\Serde\Sequence;
use Crell\Serde\Serializer;
use function Crell\fp\headtail;
use function Crell\fp\reduceWithKeys;

class JsonStreamFormatter implements Formatter
{
    use StreamFormatter;

    public function format(): string
    {
        return 'json-stream';
    }

    /**
     * @param FormatterStream $runningValue
     */
    public function serializeSequence(mixed $runningValue, Field $field, Sequence $next, Serializer $serializer): FormatterStream
    {
        $runningValue->write('[');

        $runningValue = headtail($runningValue,
            static fn (FormatterStream $runningValue, CollectionItem $item) =>  $serializer->serialize($item->value, $runningValue->unnamedContext(), $item->field),
            static function (FormatterStream $runningValue, CollectionItem $item) use ($serializer) {
                $runningValue->write(',');
                $serializer->serialize($item->value, $runningValue->unnamedContext(), $item->field);
                return $runningValue;
            }
        )($next->items);

        $runningValue->write(']');

        return $runningValue;
    }

    public function serializeString(mixed $runningValue, Field $field, ?string $next): mixed
    {
        $runningValue->printf('"%s"', is_string($next) ? str_replace('"', '\"', $next) : null);
        return $runningValue;
    }

    /**
     * @param FormatterStream $runningValue
     */
    public function serializeDictionary(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): FormatterStream
    {
        if ($runningValue->isNamedContext()) {
            $runningValue->write("\"$field->serializedName\":");
        }

        $runningValue->write('{');

        $runningValue = headtail($runningValue,
            static function (FormatterStream $runningValue, CollectionItem $item) use ($serializer) {
                $runningValue->printf('"%s":', $item->field->serializedName);
                $serializer->serialize($item->value, $runningValue->unnamedContext(), $item->field);
                return $runningValue;
            },
            static function (FormatterStream $runningValue, CollectionItem $item) use ($serializer) {
                $runningValue->write(',');
                $runningValue->printf('"%s":', $item->field->serializedName);
                $serializer->serialize($item->value, $runningValue->unnamedContext(), $item->field);
                return $runningValue;
            }
        )($next->items);

        // In the very weird case that the object has no properties but
        // does have a type map, this will break with an extra , in the
        // output. Don't bother fixing it unless someone actually tries
        // doing that for a good reason.
        reduceWithKeys($runningValue, function(FormatterStream $runningValue, $v, $k) {
            $runningValue->write(',');
            $runningValue->printf('"%s":"%s"\n', $k, $v);
        })($field->extraProperties);

        $runningValue->write('}');

        return $runningValue;
    }

    public function serializeObject(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): FormatterStream
    {
        return $this->serializeDictionary($runningValue, $field, $next, $serializer);
    }

    /**
     * @param FormatterStream $runningValue
     */
    public function serializeNull(mixed $runningValue, Field $field, mixed $next): mixed
    {
        $runningValue->write('null');
        return $runningValue;
    }

}
