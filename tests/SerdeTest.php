<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\SupportsCollecting;
use Crell\Serde\PropertyHandler\MappedObjectPropertyReader;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\BackedSize;
use Crell\Serde\Records\Pagination\DetailedResults;
use Crell\Serde\Records\Pagination\NestedPagination;
use Crell\Serde\Records\Pagination\PaginationState;
use Crell\Serde\Records\Pagination\ProductType;
use Crell\Serde\Records\RootMap\Type;
use Crell\Serde\Records\RootMap\TypeB;
use Crell\Serde\Records\CircularReference;
use Crell\Serde\Records\Drupal\EmailItem;
use Crell\Serde\Records\Drupal\FieldItemList;
use Crell\Serde\Records\Drupal\LinkItem;
use Crell\Serde\Records\Drupal\Node;
use Crell\Serde\Records\Drupal\StringItem;
use Crell\Serde\Records\Drupal\TextItem;
use Crell\Serde\Records\EmptyData;
use Crell\Serde\Records\Exclusions;
use Crell\Serde\Records\Flattening;
use Crell\Serde\Records\MangleNames;
use Crell\Serde\Records\MappedCollected\ThingA;
use Crell\Serde\Records\MappedCollected\ThingB;
use Crell\Serde\Records\MappedCollected\ThingC;
use Crell\Serde\Records\MappedCollected\ThingList;
use Crell\Serde\Records\NativeSerUn;
use Crell\Serde\Records\NestedFlattenObject;
use Crell\Serde\Records\NestedObject;
use Crell\Serde\Records\OptionalPoint;
use Crell\Serde\Records\Pagination\Pagination;
use Crell\Serde\Records\Pagination\Product;
use Crell\Serde\Records\Pagination\Results;
use Crell\Serde\Records\Point;
use Crell\Serde\Records\Shapes\Box;
use Crell\Serde\Records\Shapes\Circle;
use Crell\Serde\Records\Shapes\Shape;
use Crell\Serde\Records\Shapes\TwoDPoint;
use Crell\Serde\Records\Size;
use Crell\Serde\Records\Tasks\BigTask;
use Crell\Serde\Records\Tasks\SmallTask;
use Crell\Serde\Records\Tasks\Task;
use Crell\Serde\Records\Tasks\TaskContainer;
use Crell\Serde\Records\Visibility;
use PHPUnit\Framework\TestCase;

/**
 * Testing base class.
 *
 * To test a specific formatter:
 *
 * - Extend this class.
 * - In setUp(), set the $formatters and $format property accordingly.
 * - Override any of the *_validate() methods desired to introspect
 *   the serialized data for that test in a format-specific way.
 */
abstract class SerdeTest extends TestCase
{
    protected array $formatters;

    protected string $format;

    protected mixed $emptyData;

    /**
     * @test
     */
    public function point(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $p1 = new Point(1, 2, 3);

        $serialized = $s->serialize($p1, $this->format);

        $this->point_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: Point::class);

        self::assertEquals($p1, $result);
    }

    protected function point_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function visibility(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $p1 = new Visibility(1, 2, 3, new Visibility(4, 5, 6));

        $serialized = $s->serialize($p1, $this->format);

        $this->visibility_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: Visibility::class);

        self::assertEquals($p1, $result);
    }

    protected function visibility_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function optional_point(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $p1 = new OptionalPoint(1, 2);

        $serialized = $s->serialize($p1, $this->format);

        $this->optional_point_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: OptionalPoint::class);

        self::assertEquals($p1, $result);
    }

    protected function optional_point_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function all_fields(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

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
            objectMap: ['a' => new Point(1, 2, 3), 'b' => new Point(4, 5, 6)],
            nestedArray: [
                'a' => [1, 2, 3],
                'b' => ['a' => 1, 'b' => 2, 'c' => 3],
                'c' => 'normal',
                // I don't think this is even possible to support on deserialization,
                // as there is nowhere to inject the necessary type information.
                //'d' => [new Point(1, 2, 3), new Point(4, 5, 6)],
            ],
            size: Size::Large,
            backedSize: BackedSize::Large,
//            untyped: 'beep',
        );

        $serialized = $s->serialize($data, $this->format);

        $this->all_fields_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: AllFieldTypes::class);

        self::assertEquals($data, $result);
    }

    protected function all_fields_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function empty_input(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $serialized = $this->emptyData;

        $result = $s->deserialize($serialized, from: $this->format, to: AllFieldTypes::class);

        self::assertEquals(new AllFieldTypes(), $result);
    }

    /**
     * @test
     */
    public function name_mangling(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new MangleNames(
            customName: 'Larry',
            toUpper: 'value',
            toLower: 'value',
            prefix: 'value',
        );

        $serialized = $s->serialize($data, $this->format);

        $this->name_mangling_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: MangleNames::class);

        self::assertEquals($data, $result);
    }

    protected function name_mangling_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function flattening(): void
    {
        foreach ($this->formatters as $formatter) {
            if (($formatter->format() === $this->format) && !$formatter instanceof SupportsCollecting) {
                $this->markTestSkipped('Skipping flattening tests on non-flattening formatters');
            }
        }

        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new Flattening(
            first: 'Larry',
            last: 'Garfield',
            other: ['a' => 'A', 'b' => 2, 'c' => 'C'],
        );

        $serialized = $s->serialize($data, $this->format);

        $this->flattening_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: Flattening::class);

        self::assertEquals($data, $result);
    }

    protected function flattening_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function custom_object_reader(): void
    {
        $customHandler = new MappedObjectPropertyReader(
            supportedTypes: [Task::class],
            typeMap: new TypeMap(key: 'size', map: [
                'big' => BigTask::class,
                'small' => SmallTask::class,
            ]),
        );

        $s = new SerdeCommon(handlers: [$customHandler], formatters: $this->formatters);

        $data = new TaskContainer(
            task: new BigTask('huge'),
        );

        $serialized = $s->serialize($data, $this->format);

        $this->custom_object_reader_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: TaskContainer::class);

        self::assertEquals($data, $result);
    }

    protected function custom_object_reader_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function dynamic_type_map(): void
    {
        $customHandler = new MappedObjectPropertyReader(
            supportedTypes: [Task::class],
            typeMap: new class implements TypeMapper {
                public function keyField(): string
                {
                    return 'size';
                }

                public function findClass(string $id): ?string
                {
                    // Or do a DB lookup or whatever.
                    return match ($id) {
                        'small' => SmallTask::class,
                        'big' => BigTask::class,
                    };
                }

                public function findIdentifier(string $class): ?string
                {
                    return match ($class) {
                        SmallTask::class => 'small',
                        BigTask::class => 'big',
                    };
                }
            },
        );

        $s = new SerdeCommon(handlers: [$customHandler], formatters: $this->formatters);

        $data = new TaskContainer(
            task: new BigTask('huge'),
        );

        $serialized = $s->serialize($data, $this->format);

        $this->custom_type_map_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: TaskContainer::class);

        self::assertEquals($data, $result);
    }

    protected function custom_type_map_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function typemap_on_parent_class(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new Box(new Circle(new TwoDPoint(1, 2), 3));

        $serialized = $s->serialize($data, $this->format);

        $this->typemap_on_parent_class_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: Box::class);

        self::assertEquals($data, $result);
    }

    protected function typemap_on_parent_class_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function classname_typemap(): void
    {
        $customHandler = new MappedObjectPropertyReader(
            supportedTypes: [Shape::class],
            typeMap: new ClassNameTypeMap(key: 'class'),
        );

        $s = new SerdeCommon(handlers: [$customHandler], formatters: $this->formatters);

        $data = new Box(new Circle(new TwoDPoint(1, 2), 3));

        $serialized = $s->serialize($data, $this->format);

        $this->classname_typemap_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: Box::class);

        self::assertEquals($data, $result);
    }

    protected function classname_typemap_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function circular_detection(): void
    {
        $this->expectException(CircularReferenceDetected::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        $a = new CircularReference('A');
        $b = new CircularReference('B');
        $c = new CircularReference('C');
        $a->ref = $b;
        $b->ref = $c;
        $c->ref = $a;

        // This should throw an exception when the loop is detected.
        $serialized = $s->serialize($a, $this->format);
    }

    /**
     * @test
     */
    public function root_type_map(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new TypeB('Bob');

        $serialized = $s->serialize($data, $this->format);

        $this->root_type_map_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: Type::class);

        self::assertEquals($data, $result);
    }

    protected function root_type_map_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function bad_type_map(): void
    {
        $this->expectException(NoTypeMapDefinedForKey::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        $array = [
            'type' => 'c',
            'name' => 'Carl',
        ];

        // This should throw an exception because there is no mapping for type 'c'.
        $s->deserialize($array, from: 'array', to: Type::class);
    }

    /**
     * @test
     */
    public function nested_objects(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new NestedObject('First',
            new NestedObject('Second',
                new NestedObject('Third',
                    new NestedObject('Fourth'))));

        $serialized = $s->serialize($data, $this->format);

        $this->nested_objects_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: NestedObject::class);

        self::assertEquals($data, $result);
    }

    protected function nested_objects_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function nested_objects_with_flattening(): void
    {
        foreach ($this->formatters as $formatter) {
            if (($formatter->format() === $this->format) && !$formatter instanceof SupportsCollecting) {
                $this->markTestSkipped('Skipping flattening tests on non-flattening formatters');
            }
        }

        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new NestedFlattenObject('First', ['a' => 'A', 'b' => 'B'],
            new NestedFlattenObject('Second', ['a' => 'A', 'b' => 'B'],
                new NestedFlattenObject('Third', ['a' => 'A', 'b' => 'B'],
                    new NestedFlattenObject('Fourth', ['a' => 'A', 'b' => 'B']))));

        $serialized = $s->serialize($data, $this->format);

        $this->nested_objects_with_flattening_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: NestedFlattenObject::class);

        self::assertEquals($data, $result);
    }

    protected function nested_objects_with_flattening_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function empty_values(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new EmptyData('beep');

        $serialized = $s->serialize($data, $this->format);

        $this->empty_values_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: EmptyData::class);

        self::assertEquals($data, $result);
    }

    protected function empty_values_validate(mixed $serialized): void
    {
    }

    /**
     * @test
     */
    public function exclude_values(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new Exclusions('one', 'two');

        $serialized = $s->serialize($data, $this->format);

        $this->exclude_values_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: Exclusions::class);

        self::assertEquals('one', $result->one);
        self::assertNull($result->two ?? null);
    }

    public function exclude_values_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function drupal_example(): void
    {
        $customHandler = new MappedObjectPropertyReader(
            supportedTypes: [Records\Drupal\Field::class],
            typeMap: new class implements TypeMapper {
                public function keyField(): string
                {
                    return 'type';
                }

                public function findClass(string $id): ?string
                {
                    // Or do a DB lookup or whatever.
                    return match ($id) {
                        'string' => StringItem::class,
                        'email' => EmailItem::class,
                        'LinkItem' => LinkItem::class,
                        'text' => TextItem::class,
                    };
                }

                public function findIdentifier(string $class): ?string
                {
                    return match ($class) {
                        StringItem::class => 'string',
                        EmailItem::class => 'email',
                        LinkItem::class => 'LinkItem',
                        TextItem::class => 'text',
                    };
                }
            },
        );

        $s = new SerdeCommon(handlers: [$customHandler], formatters: $this->formatters);

        $data = new Node('A node', 3, false, false);
        $data->fields[] = new FieldItemList('en', [
            new StringItem('foo'),
            new StringItem('bar'),
        ]);
        $data->fields[] = new FieldItemList('en', [
            new EmailItem('me@example.com'),
            new EmailItem('you@example.com'),
        ]);
        $data->fields[] = new FieldItemList('en', [
            new TextItem('Stuff here', 'plain'),
            new TextItem('More things', 'raw_html'),
        ]);
        $data->fields[] = new FieldItemList('en', [
            new LinkItem(uri: 'https://typo3.com', title: 'TYPO3'),
            new LinkItem(uri: 'https://google.com', title: 'Big Evil'),
        ]);

        $serialized = $s->serialize($data, $this->format);

        $this->drupal_example_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: Node::class);

        self::assertEquals($data, $result);
    }

    public function drupal_example_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function mapped_collected_dictionary(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new ThingList(name: 'list', things: [
            'A' => new ThingA('a', 'b'),
            'B' => new ThingB('d', 'd'),
            'C' => new ThingC('e', 'f'),
        ]);

        $serialized = $s->serialize($data, $this->format);

        $this->mapped_collected_dictionary_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: ThingList::class);

        self::assertEquals($data, $result);
    }

    public function mapped_collected_dictionary_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function mapped_collected_sequence(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new ThingList(name: 'list', things: [
            new ThingA('a', 'b'),
            new ThingB('d', 'd'),
            new ThingC('e', 'f'),
        ]);

        $serialized = $s->serialize($data, $this->format);

        $this->mapped_collected_sequence_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: ThingList::class);

        self::assertEquals($data, $result);
    }

    public function mapped_collected_sequence_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function pagination_flatten_object(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new Results(
            pagination: new Pagination(
                total: 500,
                offset: 40,
                limit: 10,
            ),
            products: [
                new Product('Widget', 4.95),
                new Product('Gadget', 99.99),
                new Product('Dohickey', 11.50),
            ]
        );

        $serialized = $s->serialize($data, $this->format);

        $this->pagination_flatten_object_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: Results::class);

        self::assertEquals($data, $result);
    }

    public function pagination_flatten_object_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function pagination_flatten_multiple_object(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

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

        $serialized = $s->serialize($data, $this->format);

        $this->pagination_flatten_multiple_object_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: DetailedResults::class);

        self::assertEquals($data, $result);
    }

    public function pagination_flatten_multiple_object_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function native_object_serialization(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new NativeSerUn(1, 'beep', new \DateTimeImmutable('1918-11-11 11:11:11', new \DateTimeZone('America/Chicago')));

        $serialized = $s->serialize($data, $this->format);

        $this->native_object_serialization_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: NativeSerUn::class);

        self::assertEquals($data, $result);
    }

    public function native_object_serialization_validate(mixed $serialized): void
    {

    }

}
