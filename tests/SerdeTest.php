<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\PropertyHandler\MappedObjectPropertyReader;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\BackedSize;
use Crell\Serde\Records\CircularReference;
use Crell\Serde\Records\Flattening;
use Crell\Serde\Records\MangleNames;
use Crell\Serde\Records\OptionalPoint;
use Crell\Serde\Records\Point;
use Crell\Serde\Records\Shapes\Box;
use Crell\Serde\Records\Shapes\Circle;
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

    /**
     * @test
     */
    public function point(): void
    {
        $s = new Serde(formatters: $this->formatters);

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
        $s = new Serde(formatters: $this->formatters);

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
        $s = new Serde(formatters: $this->formatters);

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
    public function allFields(): void
    {
        $s = new Serde(formatters: $this->formatters);

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
                // I don't think this is even possible to support on deserialization,
                // as there is nowhere to inject the necessary type information.
                //'d' => [new Point(1, 2, 3), new Point(4, 5, 6)],
            ],
            size: Size::Large,
            backedSize: BackedSize::Large,
//            untyped: 'beep',
        );

        $serialized = $s->serialize($data, $this->format);

        $this->allFields_validate($serialized);

        $result = $s->deserialize($serialized, from: $this->format, to: AllFieldTypes::class);

        self::assertEquals($data, $result);
    }

    protected function allFields_validate(mixed $serialized): void
    {

    }

    /**
     * @test
     */
    public function name_mangling(): void
    {
        $s = new Serde(formatters: $this->formatters);

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
        $s = new Serde(formatters: $this->formatters);

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

        $s = new Serde(handlers: [$customHandler], formatters: $this->formatters);

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

        $s = new Serde(handlers: [$customHandler], formatters: $this->formatters);

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
        $s = new Serde(formatters: $this->formatters);

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
    public function circular_detection(): void
    {
        $this->expectException(CircularReferenceDetected::class);

        $s = new Serde(formatters: $this->formatters);

        $a = new CircularReference('A');
        $b = new CircularReference('B');
        $c = new CircularReference('C');
        $a->ref = $b;
        $b->ref  =$c;
        $c->ref  =$a;

        // This should throw an error when the loop is detected.
        $serialized = $s->serialize($a, $this->format);
    }
}
