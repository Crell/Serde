<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\FormatterStream;
use Crell\Serde\Formatter\JsonStreamFormatter;
use Crell\Serde\Records\Flattening;
use Crell\Serde\Records\MangleNames;
use Crell\Serde\Records\NestedFlattenObject;
use Crell\Serde\Records\OptionalPoint;
use Crell\Serde\Records\Pagination\DetailedResults;
use Crell\Serde\Records\Pagination\NestedPagination;
use Crell\Serde\Records\Pagination\PaginationState;
use Crell\Serde\Records\Pagination\Product;
use Crell\Serde\Records\Pagination\ProductType;
use Crell\Serde\Records\Point;
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

        //var_dump($json);

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

    }
}
