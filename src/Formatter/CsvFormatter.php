<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\CsvFormatRequiresExplicitRowType;
use Crell\Serde\Deserializer;
use Crell\Serde\TypeMismatch;
use function Crell\fp\amap;
use function Crell\fp\explode;
use function Crell\fp\pipe;

class CsvFormatter implements Formatter, Deformatter, SupportsCollecting
{
    use ArrayBasedFormatter;
    use ArrayBasedDeformatter;

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
        return 'csv';
    }

    /**
     * @param Field $rootField
     * @return array<string, mixed>
     */
    public function serializeInitialize(ClassSettings $classDef, Field $rootField): array
    {
        return ['root' => []];
    }

    /**
     * PHP's built-in csv writer only works on streams, so use a temp stream to let it.
     */
    public function serializeFinalize(mixed $runningValue, ClassSettings $classDef): string
    {
        $stream = fopen('php://temp/', 'wb') ?: throw new \RuntimeException('Failed to create temp stream.');

        $records = $runningValue['root'][array_key_first($classDef->properties)];

        foreach ($records as $line) {
            fputcsv(stream: $stream, fields: $line, separator: $this->separator, enclosure: $this->enclosure, escape: $this->escape, eol: $this->eol);

            //fputcsv($stream, $line, $this->separator, $this->enclosure, $this->escape, $this->eol);
        }

        fseek($stream, 0);
        return stream_get_contents($stream) ?: throw new \RuntimeException('Failed to get stream contents.');
    }

    public function deserializeInitialize(
        mixed $serialized,
        ClassSettings $classDef,
        Field $rootField,
        Deserializer $deserializer
    ): mixed
    {
        $rowField = $classDef->properties[array_key_first($classDef->properties)];

        $typeField = $rowField->typeField;
        if (! $typeField instanceof SequenceField) {
            throw CsvFormatRequiresExplicitRowType::create($classDef, $rowField);
        }
        // The row must be an object, to an array type of a primitive doesn't make sense.
        if (!$typeField->arrayType || !is_string($typeField->arrayType) || !class_exists($typeField->arrayType)) {
            throw CsvFormatRequiresExplicitRowType::create($classDef, $rowField);
        }

        /** @var class-string $rowType */
        $rowType = $typeField->arrayType;

        $rowDef = $deserializer->analyzer->analyze($rowType, ClassSettings::class);

        $fieldNames = array_keys($rowDef->properties);
        $fields = array_values($rowDef->properties);

        // We need to do this a bit manually because we need to line up the
        // data with the corresponding Field for safer type folding.
        $normalize = function (array $row) use ($fields): array {
            $count = count($fields);
            $ret = [];
            for ($i = 0; $i < $count; ++$i) {
                $ret[] = $this->typeNormalize($row[$i], $fields[$i]);
            }
            return $ret;
        };

        $rows = pipe(trim($serialized),
            explode($this->eol),
            amap(fn(string $line): array => str_getcsv($line, $this->separator, $this->enclosure, $this->escape)),
            amap(fn(array $vals): array => $normalize($vals)),
            amap(fn(array $vals): array => array_combine($fieldNames, $vals)),
        );

        return ['root' => [array_key_first($classDef->properties) => $rows]];
    }

    /**
     * Normalizes a scalar value to its most-restrictive type.
     *
     * CSV values are always imported as strings, but if we want to
     * push them into well-typed fields we need to cast them
     * appropriately.
     *
     * @param string $val
     *   The value to normalize.
     * @return int|float|string|bool
     *   The passed value, but now with the correct type.
     */
    private function typeNormalize(string $val, Field $field): int|float|string|bool
    {
        return match ($field->phpType) {
            'string' => $val,
            'float' => is_numeric($val)
                ? (float) $val
                : throw TypeMismatch::create($field->serializedName, $field->phpType, get_debug_type($val)),
            'int' => (is_numeric($val) && floor((float) $val) === (float) $val)
                ? (int) $val
                : throw TypeMismatch::create($field->serializedName, $field->phpType, get_debug_type($val)),
            'bool' => in_array(strtolower($val), [1, '1', 'true', 'yes', 'on'], false),
            default => throw TypeMismatch::create($field->serializedName, $field->phpType, get_debug_type($val)),
        };
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }
}
