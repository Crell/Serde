<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\AllFieldTypesReadonly;
use Crell\Serde\Records\CustomNames;
use Crell\Serde\Records\Drupal\EmailItem;
use Crell\Serde\Records\Drupal\FieldItemList;
use Crell\Serde\Records\Drupal\LinkItem;
use Crell\Serde\Records\Drupal\Node;
use Crell\Serde\Records\Drupal\StringItem;
use Crell\Serde\Records\Drupal\TextItem;
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
    public function round_trip(object $subject, ?array $fields = null, ?callable $tests = null): void
    {
        $serde = $this->getSerde();
        $serialized = $serde->serialize($subject);

        var_dump($serialized);

        $deserialized = $serde->deserialize($serialized, $subject::class);

        var_dump($deserialized);

        $fields ??= $this->getFields($subject::class);

        foreach ($fields as $field) {
            self::assertEquals($subject->$field, $deserialized->$field);
        }

        if ($tests) {
            $tests($subject, $serialized, $deserialized);
        }
    }

    /**
     * @test-disable
     * @dataProvider roundTripProvider81
     * @requires PHP >= 8.1
     */
    public function round_trip_81(object $subject, ?array $fields = null, ?callable $tests = null): void
    {
        $this->round_trip($subject, $fields, $tests);
    }

    public function roundTripProvider(): iterable
    {
//
//        yield Point::class => [
//            'subject' => new Point(1, 2, 3),
//        ];
//
//        yield AllFieldTypes::class => [
//            'subject' => new AllFieldTypes(
//                anint: 1,
//                string: 'beep',
//                afloat: 5.5,
//                bool: true,
//                dateTimeImmutable: new \DateTimeImmutable('2021-08-06 15:48:25'),
//                dateTime: new \DateTime('2021-08-06 15:48:25'),
//                simpleArray: [1, 2, 3],
//                assocArray: ['a' => 'A', 'b' => 'B'],
//                simpleObject: new Point(1, 2, 3),
//                untyped: 5,
////                resource: \fopen(__FILE__, 'rb'),
//            ),
//        ];


        $node = new Node('A node', 3, false, false);
        $node->nid = 1; // Would normally be automated somewhere.
        $node->fields[] = new FieldItemList('en', [
            new StringItem('foo'),
            new StringItem('bar'),
        ]);
        $node->fields[] = new FieldItemList('en', [
            new EmailItem('me@example.com'),
            new EmailItem('you@example.com'),
        ]);
        $node->fields[] = new FieldItemList('en', [
            new TextItem('Stuff here', 'plain'),
            new TextItem('More things', 'raw_html'),
        ]);
        $node->fields[] = new FieldItemList('en', [
            new LinkItem(uri: 'https://typo3.com', title: 'TYPO3'),
            new LinkItem(uri: 'https://google.com', title: 'Big Evil'),
        ]);

        yield "DrupalNode" => [
            'subject' => $node,
            'fields' => null,
            'tests' => function (Node $original, string $serialized, Node $deserialized) {
                print "Beep\n";
                var_dump($deserialized->fields[0]);
                self::assertInstanceOf(FieldItemList::class, $deserialized->fields[0]);
            },
        ];
    }

    public function roundTripProvider81(): iterable
    {
        yield Point::class => [
            'subject' => new Point(1, 2, 3),
        ];

        yield AllFieldTypesReadonly::class => [
            'subject' => new AllFieldTypesReadonly(
                anint: 1,
                string: 'beep',
                afloat: 5.5,
                bool: true,
                dateTimeImmutable: new \DateTimeImmutable('2021-08-06 15:48:25'),
                dateTime: new \DateTime('2021-08-06 15:48:25'),
                simpleArray: [1, 2, 3],
                assocArray: ['a' => 'A', 'b' => 'B'],
                simpleObject: new Point(1, 2, 3),
            ),
        ];
    }

    /**
     * @test
     */
    public function changes(): void
    {
        $subject = new CustomNames(first: 'Larry', last: 'Garfield');

        $serde = $this->getSerde();
        $serialized = $serde->serialize($subject);

        $expectedJson = json_encode(['firstName' => 'Larry', 'lastName' => 'Garfield']);

        self::assertEquals($expectedJson, $serialized);

        $deserialized = $serde->deserialize($serialized, $subject::class);

        $fields ??= $this->getFields($subject::class);

        foreach ($fields as $field) {
            self::assertEquals($subject->$field, $deserialized->$field);
        }
    }

    protected function getFields(string $class): array
    {
        $analyzer = new Analyzer();
        // @todo Generalize this.
        $classDef = $analyzer->analyze($class, ClassDef::class);

        return array_map(static fn(Field $f) => $f->phpName, $classDef->properties);
    }

}
