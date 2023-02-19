<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\CsvFormatter;
use Crell\Serde\Records\CsvRow;
use Crell\Serde\Records\CsvTable;
use Crell\Serde\Records\Point;
use Crell\Serde\Records\PointList;
use PHPUnit\Framework\TestCase;

class CsvFormatterTest extends TestCase
{
    /**
     * @test
     * @dataProvider csvExamples()
     */
    public function csv_serialize(object $data): void
    {
        $s = new SerdeCommon(formatters: [new CsvFormatter()]);

        $result = $s->serialize($data, format: 'csv');

        $deserialized = $s->deserialize($result, from: 'csv', to: $data::class);

        self::assertEquals($data, $deserialized);
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
    }

}
