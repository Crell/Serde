<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\SequenceField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SequenceFieldTest extends TestCase
{

    /**
     * @param string[] $expected
     */
    #[Test, DataProvider('explodeExamples')]
    public function explode(string $implodeOn, string $in, array $expected): void
    {
        $s = new SequenceField(implodeOn: $implodeOn);

        $result = $s->explode($in);

        self::assertEquals($expected, $result);
    }

    public static function explodeExamples(): iterable
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
