<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\Field;

/**
 * Indicates a Deformatter that supports determining the type of a value from the incoming data.
 *
 * Depending on the format, the format itself may include type information or it may be using
 * a PHP array as an intermediary, allowing it to just read from the value itself.
 */
interface SupportsTypeIntrospection
{
    /**
     * @param mixed $decoded
     *   The opaque decoded data source.
     * @param Field $field
     *   The field that is being read.  The $field->serializedName field is the
     *   name of the field for which we want the type.
     * @return string
     *   The type of the specified Field.  This must be a valid PHP type.  So
     *   not "uint32", just "int," for example.  PHP class names are allowed.
     */
    public function getType(mixed $decoded, Field $field): string;
}
