<?php

declare(strict_types=1);

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Formatter\JsonFormatter;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\BackedSize;
use Crell\Serde\Records\Pagination\DetailedResults;
use Crell\Serde\Records\Pagination\NestedPagination;
use Crell\Serde\Records\Pagination\PaginationState;
use Crell\Serde\Records\Pagination\Product;
use Crell\Serde\Records\Pagination\ProductType;
use Crell\Serde\Records\Point;
use Crell\Serde\Records\Size;
use Crell\Serde\SerdeCommon;

require 'vendor/autoload.php';

function setupAnalyzer(): ClassAnalyzer
{
    $analyzer = new MemoryCacheAnalyzer(new Analyzer());

    $analyzer->analyze(AllFieldTypes::class, ClassSettings::class);
    $analyzer->analyze(Point::class, ClassSettings::class);
    $analyzer->analyze(Size::class, ClassSettings::class);
    $analyzer->analyze(BackedSize::class, ClassSettings::class);

    return $analyzer;
}

function run(ClassAnalyzer $analyzer): void
{

    $serde = new SerdeCommon(
        analyzer: $analyzer,
    );

    $data = new AllFieldTypes(
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
        nestedArray: [
            'a' => [1, 2, 3],
            'b' => ['a' => 1, 'b' => 2, 'c' => 3],
            'c' => 'normal',
        ],
        size: Size::Large,
        backedSize: BackedSize::Large,
    );

    $serialized = $serde->serialize($data, 'array');
    $result = $serde->deserialize($serialized, from: 'array', to: AllFieldTypes::class);

    $data = new DetailedResults(
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
    );

    $serialized = $serde->serialize($data, 'array');
    $result = $serde->deserialize($serialized, from: 'array', to: DetailedResults::class);
}

$analyzer = setupAnalyzer();

for ($i=0; $i < 100; ++$i) {
    run($analyzer);
}
