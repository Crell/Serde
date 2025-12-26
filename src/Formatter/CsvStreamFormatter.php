<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\Field;
use Crell\Serde\CollectionItem;
use Crell\Serde\Dict;
use Crell\Serde\Sequence;
use Crell\Serde\Serializer;
use function Crell\fp\headtail;
use function Crell\fp\reduce;

/**
 * Writes CSV data to a stream handle.
 *
 * This class currently makes no optimizations for the fact
 * that the possible structure is so simple, and the existence
 * of PHP's csv functions.  That should be considered for optimization.
 */
class CsvStreamFormatter implements Formatter
{
    use StreamFormatter;

    /**
     *
     * @param string $separator
     *   The optional delimiter parameter sets the field
     *   delimiter (one character only).
     * @param string $enclosure
     *   The optional enclosure parameter sets the field
     *   enclosure (one character only).
     * @param string $escape
     *   The optional escape_char parameter sets the escape character (one character only).
     * @param string $eol
     *   The end-of-line character to use.
     */
    public function __construct(
        private readonly string $separator = ",",
        private readonly string $enclosure = '"',
        private readonly string $escape = "\\",
        private readonly string $eol = PHP_EOL
    ) {}

    public function format(): string
    {
        return 'csv-stream';
    }

    /**
     * @param FormatterStream $runningValue
     */
    public function serializeString(mixed $runningValue, Field $field, ?string $next): mixed
    {
        $next = str_replace($this->enclosure, $this->escape . $this->enclosure, $next ?? '');
        $runningValue->write($this->enclosure . $next . $this->enclosure);
        return $runningValue;
    }

    /**
     * @param FormatterStream $runningValue
     */
    public function serializeSequence(mixed $runningValue, Field $field, Sequence $next, Serializer $serializer): FormatterStream
    {
        return reduce($runningValue, static fn (FormatterStream $runningValue, CollectionItem $item)
            =>  $serializer->serialize($item->value, $runningValue, $item->field)
        )($next->items);

    }

    /**
     * @param FormatterStream $runningValue
     */
    public function serializeDictionary(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): mixed
    {
        $runningValue = headtail($runningValue,
            static function (FormatterStream $runningValue, CollectionItem $item) use ($serializer) {
                $serializer->serialize($item->value, $runningValue, $item->field);
                return $runningValue;
            },
            function (FormatterStream $runningValue, CollectionItem $item) use ($serializer) {
                $runningValue->write($this->separator);
                $serializer->serialize($item->value, $runningValue, $item->field);
                return $runningValue;
            }
        )($next->items);

        $runningValue->write($this->eol);

        return $runningValue;
    }

    public function serializeObject(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): mixed
    {
        return $this->serializeDictionary($runningValue, $field, $next, $serializer);
    }

    /**
     * @param FormatterStream $runningValue
     */
    public function serializeNull(mixed $runningValue, Field $field, mixed $next): mixed
    {
        // For null, write nothing. It will result in an empty space in the row.
        return $runningValue;
    }
}
