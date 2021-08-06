<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\Point;
use PHPUnit\Framework\TestCase;

class SerdeTest extends TestCase
{
    protected function getSerde(): JsonSerde
    {
        return new JsonSerde(new Analyzer());
    }

    /**
     * @test
     * @dataProvider roundTripProvider
     */
    public function round_trip(object $subject, ?array $fields = null): void
    {
        $serde = $this->getSerde();
        $serialized = $serde->serialize($subject);

        var_dump($serialized);

        $deserialized = $serde->deserialize($serialized, $subject::class);

        $fields ??= $this->getFields($subject::class);

        foreach ($fields as $field) {
            self::assertEquals($subject->$field, $deserialized->$field);
        }
    }

    public function roundTripProvider(): iterable
    {
        yield Point::class => [
            'subject' => new Point(1, 2, 3),
        ];

        yield AllFieldTypes::class => [
            'subject' => new AllFieldTypes(
                anint: 1,
                string: 'beep',
                afloat: 5.5,
                bool: true,
                dateTimeImmutable: new \DateTimeImmutable('2021-08-06 15:48:25'),
                dateTime: new \DateTime('2021-08-06 15:48:25'),
                simpleArray: [1, 2, 3],
                assocArray: ['a' => 'A', 'b' => 'B'],
                simpleObject: new Point(1, 2, 3),
                untyped: 5,
//                resource: \fopen(__FILE__, 'rb'),
            ),
        ];
    }

    protected function getFields(string $class): array
    {
        $analyzer = new Analyzer();
        // @todo Generalize this.
        $classDef = $analyzer->analyze($class, ClassDef::class);

        return array_map(static fn(Field $f) => $f->name, $classDef->properties);
    }

}
