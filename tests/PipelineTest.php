<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\Serde\AST\BooleanValue;
use Crell\Serde\AST\DateTimeValue;
use Crell\Serde\AST\DictionaryValue;
use Crell\Serde\AST\FloatValue;
use Crell\Serde\AST\IntegerValue;
use Crell\Serde\AST\SequenceValue;
use Crell\Serde\AST\StringValue;
use Crell\Serde\AST\StructValue;
use Crell\Serde\AST\Value;
use Crell\Serde\Decoder\GeneralDecoder;
use Crell\Serde\Decoder\ObjectDecoder;
use Crell\Serde\Records\Address;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\Employee;
use Crell\Serde\Records\Point;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{
    /**
     * @test
     */
    public function point(): void
    {
        //$p = new Pipeline(source: new ObjectDecoder(), target: new ArrayEncoder());

        $subject = new GeneralDecoder(new ObjectDecoder(new Analyzer()));

        $result = $subject->decode(new Point(3, 5, 9));

        self::assertInstanceOf(StructValue::class, $result);
        self::assertEquals(Point::class, $result->type);
        self::assertCount(3, $result->values);
    }

    /**
     * @test
     */
    public function employee(): void
    {
        //$p = new Pipeline(source: new ObjectDecoder(), target: new ArrayEncoder());

        $subject = new GeneralDecoder(new ObjectDecoder(new Analyzer()));

        $employee = new Employee(
            first: 'Larry',
            last: 'Garfield',
            hireDate: new \DateTimeImmutable('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
            tags: ['Redhead', 'Vested'],
            address: new Address(number: 123, street: 'Main St.', city: 'Chicago', state: 'IL', zip: '60614'),
        );

        $result = $subject->decode($employee);

        self::assertInstanceOf(StructValue::class, $result);
        self::assertEquals(Employee::class, $result->type);
        self::assertCount(5, $result->values);

        // Validate the basics.
        self::assertEquals('Larry', $result->values['first']->value);
        self::assertEquals('Garfield', $result->values['last']->value);

        // Validate the date value.
        self::assertInstanceOf(DateTimeValue::class, $result->values['hireDate']);
        self::assertEquals('2021-05-01T13:30:45+00:00', $result->values['hireDate']->dateTime);
        self::assertEquals('America/Chicago', $result->values['hireDate']->dateTimeZone);
        self::assertEquals(true, $result->values['hireDate']->immutable);

        // Validate the sequence.
        self::assertInstanceOf(SequenceValue::class, $result->values['tags']);
        self::assertEquals('Redhead', $result->values['tags']->values[0]->value);
        self::assertEquals('Vested', $result->values['tags']->values[1]->value);
    }

    /**
     * @test
     */
    public function allFields(): void
    {
        $subject = new GeneralDecoder(new ObjectDecoder(new Analyzer()));

        $value = new AllFieldTypes(
            anint: 5,
            string: 'hello',
            afloat: 3.14,
            bool: true,
            dateTimeImmutable: new \DateTimeImmutable('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
            dateTime: new \DateTime('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
            simpleArray: ['a', 'b', 'c', 1, 2, 3],
            assocArray: ['a' => 'A', 'b' => 'B', 'c' => 'C'],
            simpleObject: new Point(4, 5, 6),
            untyped: 'beep',
        );

        $result = $subject->decode($value);

        self::assertInstanceOf(StructValue::class, $result);

        // Int
        self::assertInstanceOf(IntegerValue::class, $result->values['anint']);
        self::assertEquals(5, $result->values['anint']->value);

        // String
        self::assertInstanceOf(StringValue::class, $result->values['string']);
        self::assertEquals('hello', $result->values['string']->value);

        // Float
        self::assertInstanceOf(FloatValue::class, $result->values['afloat']);
        self::assertEquals(3.14, $result->values['afloat']->value);

        // Bool
        self::assertInstanceOf(BooleanValue::class, $result->values['bool']);
        self::assertEquals(true, $result->values['bool']->value);

        // DateTimeImmutable
        self::assertInstanceOf(DateTimeValue::class, $result->values['dateTimeImmutable']);
        self::assertEquals('2021-05-01T13:30:45+00:00', $result->values['dateTimeImmutable']->dateTime);
        self::assertEquals('America/Chicago', $result->values['dateTimeImmutable']->dateTimeZone);
        self::assertEquals(true, $result->values['dateTimeImmutable']->immutable);

        // DateTime
        self::assertInstanceOf(DateTimeValue::class, $result->values['dateTime']);
        self::assertEquals('2021-05-01T13:30:45+00:00', $result->values['dateTime']->dateTime);
        self::assertEquals('America/Chicago', $result->values['dateTime']->dateTimeZone);
        self::assertEquals(false, $result->values['dateTime']->immutable);

        // Sequence
        self::assertInstanceOf(SequenceValue::class, $result->values['simpleArray']);
        foreach ($value->simpleArray as $i => $val) {
            self::assertEquals($val, $result->values['simpleArray']->values[$i]->value);
        }

        // Dictionary
        self::assertInstanceOf(DictionaryValue::class, $result->values['assocArray']);
        foreach ($value->assocArray as $i => $val) {
            self::assertEquals($val, $result->values['assocArray']->values[$i]->value);
        }

        // Sub-object
        self::assertInstanceOf(StructValue::class, $result->values['simpleObject']);
        self::assertEquals(Point::class, $result->values['simpleObject']->type);
        self::assertEquals(4, $result->values['simpleObject']->values['x']->value);
        self::assertEquals(5, $result->values['simpleObject']->values['y']->value);
        self::assertEquals(6, $result->values['simpleObject']->values['z']->value);

        // Untyped
        self::assertInstanceOf(StringValue::class, $result->values['untyped']);
        self::assertEquals('beep', $result->values['untyped']->value);

    }
}
