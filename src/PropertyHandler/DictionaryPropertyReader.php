<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\Formatter\Formatter;

class DictionaryPropertyReader implements PropertyReader
{
    public function readValue(Formatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        // @todo Differentiate this from sequences.
        return $formatter->serializeArray($runningValue, $field, $value, $recursor);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return $field->phpType === 'array' && !\array_is_list($value);
    }
}
