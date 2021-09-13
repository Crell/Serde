<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\OptionalPoint;
use Crell\Serde\Records\Point;
use PHPUnit\Framework\TestCase;

class RustTest extends TestCase
{
    /**
     * @test
     */
    public function point(): void
    {
        $s = new RustSerializer();

        $p1 = new Point(1, 2, 3);

        $json = $s->serialize($p1, 'json');

        self::assertEquals('{"x":1,"y":2,"z":3}', $json);

        $result = $s->deserialize($json, from: 'json', to: Point::class);

        self::assertEquals($p1, $result);
    }

    /**
     * @test
     */
    public function optional_point(): void
    {
        $s = new RustSerializer();

        $p1 = new OptionalPoint(1, 2);

        $json = $s->serialize($p1, 'json');

        self::assertEquals('{"x":1,"y":2,"z":0}', $json);

        $result = $s->deserialize($json, from: 'json', to: OptionalPoint::class);

        self::assertEquals($p1, $result);
    }

    /**
     * @test
     */
    public function allFields(): void
    {
        $s = new RustSerializer();

        $data = new AllFieldTypes(
            anint: 5,
            string: 'hello',
            afloat: 3.14,
            bool: true,
//            dateTimeImmutable: new \DateTimeImmutable('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
//            dateTime: new \DateTime('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
            simpleArray: ['a', 'b', 'c', 1, 2, 3],
            assocArray: ['a' => 'A', 'b' => 'B', 'c' => 'C'],
//            simpleObject: new Point(4, 5, 6),
//            objectList: [new Point(1, 2, 3), new Point(4, 5, 6)],
//            untyped: 'beep',
        );

        $json = $s->serialize($data, 'json');

//        var_dump($json);
        //self::assertEquals('{"x":1,"y":2,"z":3}', $json);

        $result = $s->deserialize($json, from: 'json', to: AllFieldTypes::class);

        self::assertEquals($data, $result);
    }


}
