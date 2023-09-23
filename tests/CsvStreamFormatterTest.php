<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\CsvFormatter;
use Crell\Serde\Formatter\CsvStreamFormatter;
use Crell\Serde\Formatter\FormatterStream;
use Crell\Serde\Records\CsvRow;
use Crell\Serde\Records\CsvTable;
use Crell\Serde\Records\CsvTableLazy;
use Crell\Serde\Records\Point;
use Crell\Serde\Records\PointList;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CsvStreamFormatterTest extends TestCase
{
    #[Test, DataProvider('csvExamples')]
    public function csv_serialize(object $data, ?object $expected = null): void
    {
        $s = new SerdeCommon(formatters: [new CsvStreamFormatter(), new CsvFormatter()]);

        // Use a temp stream as a placeholder.
        $init = FormatterStream::new(fopen('php://temp/', 'wb'));

        $result = $s->serialize($data, format: 'csv-stream', init: $init);

        fseek($result->stream, 0);
        $csv = stream_get_contents($result->stream);

        $expected ??= $data;

        $deserialized = $s->deserialize($csv, from: 'csv', to: $data::class);

        self::assertEquals($expected, $deserialized);
    }

    public static function csvExamples(): iterable
    {
        yield [
            new PointList([
                new Point(1, 2, 3),
                new Point(4, 5, 6),
                new Point(7, 8, 9),
            ]),
        ];

        yield [
            new CsvTable([
                new CsvRow('Larry', 100, 500),
                new CsvRow('Curly', 25, 25.25),
                new CsvRow('Moe', 31, 99.9999),
            ]),
        ];

        $rows = static function() {
            yield new CsvRow('Larry', 100, 500);
            yield new CsvRow('Curly', 25, 25.25);
            yield new CsvRow('Moe', 31, 99.9999);
        };

        yield [
            'data' => new CsvTableLazy($rows()),
            'expected' => new CsvTableLazy([
                new CsvRow('Larry', 100, 500),
                new CsvRow('Curly', 25, 25.25),
                new CsvRow('Moe', 31, 99.9999),
            ]),
        ];
    }
}
