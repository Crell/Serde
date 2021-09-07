<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\Serde\AST\StructValue;
use Crell\Serde\AST\Value;
use Crell\Serde\Records\Address;
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
        // Temporary to force autoloading.
        class_exists(Value::class);

        //$p = new Pipeline(source: new ObjectDecoder(), target: new ArrayEncoder());

        $subject = new ObjectDecoder(new Analyzer());

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
        // Temporary to force autoloading.
        class_exists(Value::class);

        //$p = new Pipeline(source: new ObjectDecoder(), target: new ArrayEncoder());

        $subject = new ObjectDecoder(new Analyzer());

        $employee = new Employee(
            first: 'Larry',
            last: 'Garfield',
            hireDate: new \DateTimeImmutable('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
            address: new Address(number: 123, street: 'Main St.', city: 'Chicago', state: 'IL', zip: '60614'),
//            tags: ['Redhead', 'Vested'],
        );

        $result = $subject->decode($employee);

        self::assertInstanceOf(StructValue::class, $result);
        self::assertEquals(Employee::class, $result->type);
        self::assertCount(4, $result->values);

        self::assertEquals('Larry', $result->values['first']->value);
        self::assertEquals('Garfield', $result->values['last']->value);

        self::assertEquals('2021-05-01T13:30:45+00:00', $result->values['hireDate']->dateTime);
        self::assertEquals('America/Chicago', $result->values['hireDate']->dateTimeZone);
        self::assertEquals(true, $result->values['hireDate']->immutable);
    }

}
