<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Renaming\Cases;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CasesTest extends TestCase
{
    /**
     * @param Cases $case
     * @param string $in
     * @param string $expected
     */
    #[Test, DataProvider('caseExamples')]
    public function caseFold(Cases $case, string $in, string $expected): void
    {
        self::assertEquals($expected, $case->convert($in));
    }

    public static function caseExamples(): iterable
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

        yield [
            'case' => Cases::kebab_case,
            'in' => 'beepBeep',
            'expected' => 'beep-beep',
        ];

        yield [
            'case' => Cases::kebab_case,
            'in' => 'beep_Beep',
            'expected' => 'beep-beep',
        ];

        yield [
            'case' => Cases::kebab_case,
            'in' => 'BeepBoop',
            'expected' => 'beep-boop',
        ];

        yield [
            'case' => Cases::CamelCase,
            'in' => 'BeepBoop',
            'expected' => 'BeepBoop',
        ];

        yield [
            'case' => Cases::CamelCase,
            'in' => 'beepboop',
            'expected' => 'Beepboop',
        ];

        yield [
            'case' => Cases::CamelCase,
            'in' => 'beep_boop',
            'expected' => 'BeepBoop',
        ];

        yield [
            'case' => Cases::lowerCamelCase,
            'in' => 'BeepBoop',
            'expected' => 'beepBoop',
        ];

        yield [
            'case' => Cases::lowerCamelCase,
            'in' => 'beepboop',
            'expected' => 'beepboop',
        ];

        yield [
            'case' => Cases::lowerCamelCase,
            'in' => 'beep_boop',
            'expected' => 'beepBoop',
        ];

    }
}
