<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Renaming\Cases;
use PHPUnit\Framework\TestCase;

class CasesTest extends TestCase
{
    /**
     * @test
     * @dataProvider caseExamples
     *
     * @param Cases $case
     * @param string $in
     * @param string $expected
     */
    public function caseFold(Cases $case, string $in, string $expected): void
    {
        self::assertEquals($expected, $case->convert($in));
    }

    public function caseExamples(): iterable
    {
        yield [
            'case' => Cases::lowercase,
            'in' => 'BEEP',
            'expected' => 'beep',
        ];

        yield [
            'case' => Cases::UPPERCASE,
            'in' => 'Beep',
            'expected' => 'BEEP',
        ];

        yield [
            'case' => Cases::snake_case,
            'in' => 'beepBeep',
            'expected' => 'beep_beep',
        ];

        yield [
            'case' => Cases::snake_case,
            'in' => 'beep_Beep',
            'expected' => 'beep_beep',
        ];

        yield [
            'case' => Cases::snake_case,
            'in' => 'BeepBoop',
            'expected' => 'beep_boop',
        ];

    }
}
