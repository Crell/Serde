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

    protected $allFieldsArray = array (
        'Crell\\Serde\\Records\\AllFieldTypes' =>
            array (
                'anint' => 5,
                'string' => 'hello',
                'afloat' => 3.14,
                'bool' => true,
                'dateTimeImmutable' =>
                    array (
                        'timestamp' => '2021-05-01T13:30:45+00:00',
                        'timezone' => 'America/Chicago',
                        'immutable' => true,
                    ),
                'dateTime' =>
                    array (
                        'timestamp' => '2021-05-01T13:30:45+00:00',
                        'timezone' => 'America/Chicago',
                        'immutable' => false,
                    ),
                'simpleArray' =>
                    array (
                        0 => 'a',
                        1 => 'b',
                        2 => 'c',
                        3 => 1,
                        4 => 2,
                        5 => 3,
                    ),
                'assocArray' =>
                    array (
                        'a' => 'A',
                        'b' => 'B',
                        'c' => 'C',
                    ),
                'simpleObject' =>
                    array (
                        'Crell\\Serde\\Records\\Point' =>
                            array (
                                'x' => 4,
                                'y' => 5,
                                'z' => 6,
                            ),
                    ),
                'untyped' => 'beep',
            ),
    );

    protected $allFieldsJson = '{
    "Crell\\\\Serde\\\\Records\\\\AllFieldTypes": {
        "anint": 5,
        "string": "hello",
        "afloat": 3.14,
        "bool": true,
        "dateTimeImmutable": {
            "timestamp": "2021-05-01T13:30:45+00:00",
            "timezone": "America\\/Chicago",
            "immutable": true
        },
        "dateTime": {
            "timestamp": "2021-05-01T13:30:45+00:00",
            "timezone": "America\\/Chicago",
            "immutable": false
        },
        "simpleArray": [
            "a",
            "b",
            "c",
            1,
            2,
            3
        ],
        "assocArray": {
            "a": "A",
            "b": "B",
            "c": "C"
        },
        "simpleObject": {
            "Crell\\\\Serde\\\\Records\\\\Point": {
                "x": 4,
                "y": 5,
                "z": 6
            }
        },
        "untyped": "beep"
    }
}';

    protected AllFieldTypes $allFieldsObject;

    public function setUp(): void
    {
        parent::setUp();

        $this->allFieldsObject = new AllFieldTypes(
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
    }


    public function assertAllFieldsAst(Value $ast): void
    {
        self::assertInstanceOf(StructValue::class, $ast);

        // Int
        self::assertInstanceOf(IntegerValue::class, $ast->values['anint']);
        self::assertEquals(5, $ast->values['anint']->value);

        // String
        self::assertInstanceOf(StringValue::class, $ast->values['string']);
        self::assertEquals('hello', $ast->values['string']->value);

        // Float
        self::assertInstanceOf(FloatValue::class, $ast->values['afloat']);
        self::assertEquals(3.14, $ast->values['afloat']->value);

        // Bool
        self::assertInstanceOf(BooleanValue::class, $ast->values['bool']);
        self::assertEquals(true, $ast->values['bool']->value);

        // DateTimeImmutable
        self::assertInstanceOf(DateTimeValue::class, $ast->values['dateTimeImmutable']);
        self::assertEquals('2021-05-01T13:30:45+00:00', $ast->values['dateTimeImmutable']->dateTime);
        self::assertEquals('America/Chicago', $ast->values['dateTimeImmutable']->dateTimeZone);
        self::assertEquals(true, $ast->values['dateTimeImmutable']->immutable);

        // DateTime
        self::assertInstanceOf(DateTimeValue::class, $ast->values['dateTime']);
        self::assertEquals('2021-05-01T13:30:45+00:00', $ast->values['dateTime']->dateTime);
        self::assertEquals('America/Chicago', $ast->values['dateTime']->dateTimeZone);
        self::assertEquals(false, $ast->values['dateTime']->immutable);

        // Sequence
        self::assertInstanceOf(SequenceValue::class, $ast->values['simpleArray']);
        foreach ($this->allFieldsObject->simpleArray as $i => $val) {
            self::assertEquals($val, $ast->values['simpleArray']->values[$i]->value);
        }

        // Dictionary
        self::assertInstanceOf(DictionaryValue::class, $ast->values['assocArray']);
        foreach ($this->allFieldsObject->assocArray as $i => $val) {
            self::assertEquals($val, $ast->values['assocArray']->values[$i]->value);
        }

        // Sub-object
        self::assertInstanceOf(StructValue::class, $ast->values['simpleObject']);
        self::assertEquals(Point::class, $ast->values['simpleObject']->type);
        self::assertEquals(4, $ast->values['simpleObject']->values['x']->value);
        self::assertEquals(5, $ast->values['simpleObject']->values['y']->value);
        self::assertEquals(6, $ast->values['simpleObject']->values['z']->value);

        // Untyped
        self::assertInstanceOf(StringValue::class, $ast->values['untyped']);
        self::assertEquals('beep', $ast->values['untyped']->value);
    }

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

        $result = $subject->decode($this->allFieldsObject);

        $this->assertAllFieldsAst($result);
    }

    /**
     * @test
     */
    public function astToArray(): void
    {
        $decoder = new GeneralDecoder(new ObjectDecoder(new Analyzer()));

        $ast = $decoder->decode($this->allFieldsObject);

        $subject = new AstToArray();

        $result = $subject->do($ast);

        self::assertEquals($this->allFieldsArray, $result);
    }

    /**
     * @test-disable
     */
    public function AstToJson(): void
    {
        $decoder = new GeneralDecoder(new ObjectDecoder(new Analyzer()));

        $ast = $decoder->decode($this->allFieldsObject);

        $subject = new AstToJson(true);

        $result = $subject->do($ast);

        self::assertEquals($this->allFieldsJson, $result);
    }

    /**
     * @test
     */
    public function annotatedArrayToAst(): void
    {
        $subject = new AnnotatedArrayToAst();

        $result = $subject->do($this->allFieldsArray);

        $this->assertAllFieldsAst($result);
    }

    public function annotatedJsonToAst(): void
    {

    }

}
