<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\FormatterStream;
use Crell\Serde\Formatter\JsonStreamFormatter;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\BackedSize;
use Crell\Serde\Records\CsvRow;
use Crell\Serde\Records\CsvTableLazy;
use Crell\Serde\Records\Flattening;
use Crell\Serde\Records\MangleNames;
use Crell\Serde\Records\MultiCollect\ThingOneA;
use Crell\Serde\Records\MultiCollect\ThingTwoC;
use Crell\Serde\Records\MultiCollect\Wrapper;
use Crell\Serde\Records\NestedFlattenObject;
use Crell\Serde\Records\NullArrays;
use Crell\Serde\Records\OptionalPoint;
use Crell\Serde\Records\Pagination\DetailedResults;
use Crell\Serde\Records\Pagination\NestedPagination;
use Crell\Serde\Records\Pagination\PaginationState;
use Crell\Serde\Records\Pagination\Product;
use Crell\Serde\Records\Pagination\ProductType;
use Crell\Serde\Records\Point;
use Crell\Serde\Records\Size;
use Crell\Serde\Records\Visibility;
use PHPUnit\Framework\TestCase;

class JsonStreamFormatterTest extends TestCase
{
    /**
     * @test
     * @dataProvider streamExamples()
     */
    public function stream_serialize(object $data): void
    {
        $s = new SerdeCommon(formatters: [new JsonStreamFormatter()]);

        // Use a temp stream as a placeholder.
        $init = FormatterStream::new(fopen('php://temp/', 'wb'));

        $result = $s->serialize($data, format: 'json-stream', init: $init);

        fseek($result->stream, 0);
        $json = stream_get_contents($result->stream);

        $deserialized = $s->deserialize($json, from: 'json', to: $data::class);

        self::assertEquals($data, $deserialized);
    }

    /**
     * @see stream_serialize()
     */
    public function streamExamples(): iterable
    {
        yield Point::class => [
            'data' => new Point(1, 2, 3),
        ];

        yield Visibility::class => [
            'data' => new Visibility(1, 2, 3, new Visibility(4, 5, 6)),
        ];

        yield OptionalPoint::class => [
            'data' => new OptionalPoint(1, 2),
        ];

        yield MangleNames::class => [
            'data' => new MangleNames(
                customName: 'Larry',
                toUpper: 'value',
                toLower: 'value',
                prefix: 'value',
            ),
        ];

        yield Flattening::class => [
            'data' => new Flattening(
                first: 'Larry',
                last: 'Garfield',
                other: ['a' => 'A', 'b' => 2, 'c' => 'C'],
            ),
        ];

        yield NestedFlattenObject::class => [
            'data' => new NestedFlattenObject('First', ['a' => 'A', 'b' => 'B'],
                new NestedFlattenObject('Second', ['a' => 'A', 'b' => 'B'],
                    new NestedFlattenObject('Third', ['a' => 'A', 'b' => 'B'],
                        new NestedFlattenObject('Fourth', ['a' => 'A', 'b' => 'B']))))
        ];

        yield DetailedResults::class => [
            'data' => new DetailedResults(
                pagination: new NestedPagination(
                    total: 500,
                    limit: 10,
                    state: new PaginationState(40),
                ),
                type: new ProductType(
                    name: 'Beep',
                    category: 'Boop'
                ),
                products: [
                    new Product('Widget', 4.95),
                    new Product('Gadget', 99.99),
                    new Product('Dohickey', 11.50),
                ],
                other: ['narf' => 'poink', 'bleep' => 'bloop']
            ),
        ];

        yield Wrapper::class => [
            'data' => new Wrapper(
                one: new ThingOneA(
                    first: 'one',
                    second: 'two',
                ),
                two: new ThingTwoC(
                    fifth: 'five',
                    sixth: 'six',
                ),
                other: [
                    'more' => 'data',
                    'goes' => 'here',
                ]
            )
        ];

        yield AllFieldTypes::class => [
            'data' => new AllFieldTypes(
                anint: 5,
                string: 'hello',
                afloat: 3.14,
                bool: true,
                dateTimeImmutable: new \DateTimeImmutable('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
                dateTime: new \DateTime('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
                dateTimeZone: new \DateTimeZone('America/Chicago'),
                simpleArray: ['a', 'b', 'c', 1, 2, 3],
                assocArray: ['a' => 'A', 'b' => 'B', 'c' => 'C'],
                simpleObject: new Point(4, 5, 6),
                objectList: [new Point(1, 2, 3), new Point(4, 5, 6)],
                objectMap: ['a' => new Point(1, 2, 3), 'b' => new Point(4, 5, 6)],
                nestedArray: [
                    'a' => [1, 2, 3],
                    'b' => ['a' => 1, 'b' => 2, 'c' => 3],
                    'c' => 'normal',
                ],
                size: Size::Large,
                backedSize: BackedSize::Large,
                implodedSeq: [1, 2, 3],
                implodedDict: ['a' => 'A', 'b' => 'B'],
            )
        ];

        yield NullArrays::class => [
            'data' => new NullArrays(),
        ];
    }

    /**
     * @test
     */
    public function object_with_generator_streams_cleanly(): void
    {
        $s = new SerdeCommon(formatters: [new JsonStreamFormatter()]);

        // Use a temp stream as a placeholder.
        $init = FormatterStream::new(fopen('php://temp/', 'wb'));

        $rows = static function() {
            yield new CsvRow('Larry', 100, 500);
            yield new CsvRow('Curly', 25, 25.25);
            yield new CsvRow('Moe', 31, 99.9999);
        };

        $data = new CsvTableLazy($rows());

        $result = $s->serialize($data, format: 'json-stream', init: $init);

        fseek($result->stream, 0);
        $json = stream_get_contents($result->stream);

        // The deserialized version will use an array, not a generator.
        $expected = new CsvTableLazy([
            new CsvRow('Larry', 100, 500),
            new CsvRow('Curly', 25, 25.25),
            new CsvRow('Moe', 31, 99.9999),
        ]);

        $deserialized = $s->deserialize($json, from: 'json', to: $data::class);

        self::assertEquals($expected, $deserialized);
    }

}
