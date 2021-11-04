<?php

declare(strict_types=1);

namespace Crell\Serde;

use PHPUnit\Framework\TestCase;

class SequenceFieldTest extends TestCase
{

    /**
     * @test
     * @dataProvider explodeExamples
     */
    public function explode(string $implodeOn, string $in, array $expected): void
    {
        $s = new SequenceField(implodeOn: $implodeOn);

        $result = $s->explode($in);

        self::assertEquals($expected, $result);
    }

    public function explodeExamples(): iterable
    {
        yield [
            'implodeOn' => ',',
            'in' => 'beep',
            'expected' => ['beep'],
        ];
        yield [
            'implodeOn' => ',',
            'in' => '',
            'expected' => [],
        ];
        yield [
            'implodeOn' => ',',
            'in' => 'beep, boop, bleep',
            'expected' => ['beep', 'boop', 'bleep'],
        ];
        yield [
            'implodeOn' => ',',
            'in' => 'beep, boop, bleep,',
            'expected' => ['beep', 'boop', 'bleep'],
        ];
    }
}
