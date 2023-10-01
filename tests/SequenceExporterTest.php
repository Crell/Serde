<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;
use Crell\Serde\PropertyHandler\SequenceExporter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SequenceExporterTest extends TestCase
{
    #[Test, DataProvider('canExportExamples')]
    public function can_export_the_right_values(Field $field, mixed $value, bool $expected): void
    {
        $exporter = new SequenceExporter();
        $this->assertEquals($expected, $exporter->canExport($field, $value, 'array'));
    }

    public static function canExportExamples(): iterable
    {
        $sequenceArrayField = Field::create('test', phpType: 'array', typeField: new SequenceField());
        yield 'flagged sequence, array, empty' => [
            'field' => $sequenceArrayField,
            'value' => [],
            'expected' => true,
        ];
        yield 'flagged sequence, array, list' => [
            'field' => $sequenceArrayField,
            'value' => ['a', 'b', 'c'],
            'expected' => true,
        ];
        yield 'flagged sequence, array, map' => [
            'field' => $sequenceArrayField,
            'value' => ['a' => 'A', 'b' => 'B', 'c' => 'C'],
            'expected' => true,
        ];

        $sequenceIterableField = Field::create('test', phpType: 'iterable', typeField: new SequenceField());
        yield 'flagged sequence, iterable, empty' => [
            'field' => $sequenceIterableField,
            'value' => [],
            'expected' => true,
        ];
        yield 'flagged sequence, iterable, list' => [
            'field' => $sequenceIterableField,
            'value' => ['a', 'b', 'c'],
            'expected' => true,
        ];
        yield 'flagged sequence, iterable, map' => [
            'field' => $sequenceIterableField,
            'value' => ['a' => 'A', 'b' => 'B', 'c' => 'C'],
            'expected' => true,
        ];

        $nonArrayField = Field::create('test', phpType: 'float');
        yield 'unflagged, non-array, map' => [
            'field' => $nonArrayField,
            'value' => ['a' => 'A', 'b' => 'B', 'c' => 'C'],
            'expected' => false,
        ];

        $dictionaryField = Field::create('test', phpType: 'array', typeField: new DictionaryField());
        yield 'flagged dictionary, array, empty' => [
            'field' => $dictionaryField,
            'value' => [],
            'expected' => false,
        ];
        yield 'flagged dictionary, array, list' => [
            'field' => $dictionaryField,
            'value' => ['a', 'b', 'c'],
            'expected' => false,
        ];
        yield 'flagged dictionary, array, map' => [
            'field' => $dictionaryField,
            'value' => ['a' => 'A', 'b' => 'B', 'c' => 'C'],
            'expected' => false,
        ];

        $unmarkedArrayField = Field::create('test', phpType: 'array');
        yield 'unflagged, array, list' => [
            'field' => $unmarkedArrayField,
            'value' => ['a', 'b', 'c'],
            'expected' => true,
        ];
        yield 'unflagged, array, map' => [
            'field' => $unmarkedArrayField,
            'value' => ['a' => 'A', 'b' => 'B', 'c' => 'C'],
            'expected' => false,
        ];
    }

    #[Test, DataProvider('canImportExamples')]
    public function can_import_the_right_values(Field $field, bool $expected): void
    {
        $exporter = new SequenceExporter();
        $this->assertEquals($expected, $exporter->canImport($field, 'array'));
    }

    public static function canImportExamples(): iterable
    {
        $sequenceArrayField = Field::create('test', phpType: 'array', typeField: new SequenceField());
        yield 'flagged sequence, array' => [
            'field' => $sequenceArrayField,
            'expected' => true,
        ];

        $sequenceIterableField = Field::create('test', phpType: 'iterable', typeField: new SequenceField());
        yield 'flagged sequence, iterable' => [
            'field' => $sequenceIterableField,
            'expected' => true,
        ];

        $nonArrayField = Field::create('test', phpType: 'float');
        yield 'unflagged, non-array, map' => [
            'field' => $nonArrayField,
            'expected' => false,
        ];

        $dictionaryField = Field::create('test', phpType: 'array', typeField: new DictionaryField());
        yield 'flagged dictionary, array' => [
            'field' => $dictionaryField,
            'expected' => false,
        ];

        $unmarkedArrayField = Field::create('test', phpType: 'array');
        yield 'unflagged, array' => [
            'field' => $unmarkedArrayField,
            'expected' => true,
        ];
    }

}
