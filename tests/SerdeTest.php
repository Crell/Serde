<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\PropertyHandler\CustomMappedObjectPropertyReader;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\BackedSize;
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

class SerdeTest extends TestCase
{
    /**
     * @test
     */
    public function point(): void
    {
        $s = new Serde();

        $p1 = new Point(1, 2, 3);

        $json = $s->serialize($p1, 'json');

        self::assertEquals('{"x":1,"y":2,"z":3}', $json);

        $result = $s->deserialize($json, from: 'json', to: Point::class);

        self::assertEquals($p1, $result);
    }

    /**
     * @test
     */
    public function visibility(): void
    {
        $s = new Serde();

        $p1 = new Visibility(1, 2, 3, new Visibility(4, 5, 6));

        $json = $s->serialize($p1, 'json');

        self::assertEquals('{"public":1,"protected":2,"private":3,"visibility":{"public":4,"protected":5,"private":6}}', $json);

        $result = $s->deserialize($json, from: 'json', to: Visibility::class);

        self::assertEquals($p1, $result);
    }

    /**
     * @test
     */
    public function optional_point(): void
    {
        $s = new Serde();

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
        $s = new Serde();

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

        $json = $s->serialize($data, 'json');

//        var_dump($json);

        $result = $s->deserialize($json, from: 'json', to: AllFieldTypes::class);

        self::assertEquals($data, $result);
    }

    /**
     * @test
     */
    public function name_mangling(): void
    {
        $s = new Serde();

        $data = new MangleNames(
            customName: 'Larry',
            toUpper: 'value',
            toLower: 'value',
            prefix: 'value',
        );

        $json = $s->serialize($data, 'json');

        $toTest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('Larry', $toTest['renamed']);
        self::assertEquals('value', $toTest['TOUPPER']);
        self::assertEquals('value', $toTest['tolower']);
        self::assertEquals('value', $toTest['beep_prefix']);

        $result = $s->deserialize($json, from: 'json', to: MangleNames::class);

        self::assertEquals($data, $result);
    }

    /**
     * @test
     */
    public function flattening(): void
    {
        $s = new Serde();

        $data = new Flattening(
            first: 'Larry',
            last: 'Garfield',
            other: ['a' => 'A', 'b' => 2, 'c' => 'C'],
        );

        $json = $s->serialize($data, 'json');

        $toTest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('Larry', $toTest['first']);
        self::assertEquals('Garfield', $toTest['last']);
        self::assertEquals('A', $toTest['a']);
        self::assertEquals(2, $toTest['b']);
        self::assertEquals('C', $toTest['c']);

        $result = $s->deserialize($json, from: 'json', to: Flattening::class);

        self::assertEquals($data, $result);
    }

    /**
     * @test
     */
    public function custom_object_reader(): void
    {
        $customHandler = new CustomMappedObjectPropertyReader(
            supportedTypes: [Task::class],
            typeMap: new TypeMap(key: 'size', map: [
                'big' => BigTask::class,
                'small' => SmallTask::class,
            ]),
        );

        $s = new Serde(handlers: [$customHandler]);

        $data = new TaskContainer(
            task: new BigTask('huge'),
        );

        $json = $s->serialize($data, 'json');

        $toTest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('huge', $toTest['task']['name']);
        self::assertEquals('big', $toTest['task']['size']);

        $result = $s->deserialize($json, from: 'json', to: TaskContainer::class);

        self::assertEquals($data, $result);
    }

    /**
     * @test
     */
    public function dynamic_type_map(): void
    {
        $customHandler = new CustomMappedObjectPropertyReader(
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

        $s = new Serde(handlers: [$customHandler]);

        $data = new TaskContainer(
            task: new BigTask('huge'),
        );

        $json = $s->serialize($data, 'json');

        $toTest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('huge', $toTest['task']['name']);
        self::assertEquals('big', $toTest['task']['size']);

        $result = $s->deserialize($json, from: 'json', to: TaskContainer::class);

        self::assertEquals($data, $result);
    }

    /**
     * @test
     */
    public function typemap_on_parent_class(): void
    {
        $s = new Serde();

        $data = new Box(new Circle(new TwoDPoint(1, 2), 3));

        $json = $s->serialize($data, 'json');

        $toTest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(3, $toTest['aShape']['radius']);
        self::assertEquals('circle', $toTest['aShape']['shape']);

        $result = $s->deserialize($json, from: 'json', to: Box::class);

        self::assertEquals($data, $result);
    }
}
