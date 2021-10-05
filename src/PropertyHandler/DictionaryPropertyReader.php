<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\CollectionItem;
use Crell\Serde\Dict;
use Crell\Serde\Field;
use Crell\Serde\Formatter\Formatter;

class DictionaryPropertyReader implements PropertyReader
{
    public function readValue(Formatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        $dict = new Dict();
        foreach ($value as $k => $v) {
            $f = Field::create(serializedName: $k, phpType: \get_debug_type($v));
            $dict->items[] = new CollectionItem(field: $f, value: $v);
        }

        return $formatter->serializeDictionary($runningValue, $field, $dict, $recursor);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === 'array' && !\array_is_list($value);
    }
}
