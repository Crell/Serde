<?php

declare(strict_types=1);

use Crell\Serde\Formatter\JsonFormatter;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\BackedSize;
use Crell\Serde\Records\Point;
use Crell\Serde\Records\Size;
use Crell\Serde\Serde;


function setup(): void
{
    require 'vendor/autoload.php';

    class_exists(Serde::class);
    class_exists(AllFieldTypes::class);
    class_exists(Point::class);
    class_exists(JsonFormatter::class);
    class_exists(\Crell\Serde\PropertyHandler\ObjectPropertyReader::class);
    class_exists(\Crell\Serde\PropertyHandler\SequencePropertyReader::class);
    class_exists(\Crell\Serde\PropertyHandler\ScalarPropertyReader::class);
    class_exists(\Crell\Serde\PropertyHandler\DictionaryPropertyReader::class);
    class_exists(\Crell\Serde\PropertyHandler\EnumPropertyReader::class);
    class_exists(\Crell\Serde\PropertyHandler\DateTimePropertyReader::class);
    class_exists(\Crell\Serde\PropertyHandler\DateTimeZonePropertyReader::class);
    class_exists(\Crell\Serde\PropertyHandler\PropertyReader::class);
    class_exists(\Crell\Serde\PropertyHandler\PropertyWriter::class);
    enum_exists(BackedSize::class);
    enum_exists(Size::class);
}

function run(): void
{
    $serde = new Serde(formatters: [new JsonFormatter()]);

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

    $serialized = $serde->serialize($data, 'json');

    $result = $serde->deserialize($serialized, from: 'json', to: AllFieldTypes::class);
}

setup();

for ($i=0; $i < 200; ++$i) {
    run();
}
