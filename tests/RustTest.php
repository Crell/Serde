<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\PropertyHandler\ObjectPropertyReader;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\Flattening;
use Crell\Serde\Records\MangleNames;
use Crell\Serde\Records\OptionalPoint;
use Crell\Serde\Records\Point;
use Crell\Serde\Records\Tasks\BigTask;
use Crell\Serde\Records\Tasks\Task;
use Crell\Serde\Records\Tasks\TaskContainer;
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
            dateTimeImmutable: new \DateTimeImmutable('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
            dateTime: new \DateTime('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
            simpleArray: ['a', 'b', 'c', 1, 2, 3],
            assocArray: ['a' => 'A', 'b' => 'B', 'c' => 'C'],
            simpleObject: new Point(4, 5, 6),
            objectList: [new Point(1, 2, 3), new Point(4, 5, 6)],
//            untyped: 'beep',
        );

        $json = $s->serialize($data, 'json');

//        var_dump($json);
        //self::assertEquals('{"x":1,"y":2,"z":3}', $json);

        $result = $s->deserialize($json, from: 'json', to: AllFieldTypes::class);

        self::assertEquals($data, $result);
    }

    /**
     * @test
     */
    public function name_mangling(): void
    {
        $s = new RustSerializer();

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
        $s = new RustSerializer();

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
        $customHandler = new class extends ObjectPropertyReader {
            public function readValue(
                JsonFormatter $formatter,
                callable $recursor,
                Field $field,
                mixed $value,
                mixed $runningValue
            ): mixed {
                return $formatter->serializeObject($runningValue, $field, $value, $recursor, ['size' => $value::class]);
            }

            public function canRead(Field $field, mixed $value, string $format): bool
            {
                return is_object($value) && $value instanceof Task;
            }

            public function writeValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $source): mixed
            {
                return parent::writeValue($formatter, $recursor, $field->with(phpType: $source[$field->serializedName()]['size']), $source);
            }

            public function canWrite(Field $field, string $format): bool
            {
                return $field->phpType === Task::class;
            }
        };

        $s = new RustSerializer(handlers: [$customHandler]);

        $data = new TaskContainer(
            task: new BigTask('huge'),
        );

        $json = $s->serialize($data, 'json');

        $toTest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('huge', $toTest['task']['name']);
        self::assertEquals(BigTask::class, $toTest['task']['size']);

        $result = $s->deserialize($json, from: 'json', to: TaskContainer::class);

        self::assertEquals($data, $result);
    }

}
