<?php

declare(strict_types=1);

namespace Crell\Serde;

use PHPUnit\Framework\TestCase;

class DictionaryFieldTest extends TestCase
{

    /**
     * @test
     * @dataProvider explosionExamples
     */
    public function explosion(string $implodeOn, string $joinOn, string $in, array $expected): void
    {
        $d = new DictionaryField(
            implodeOn: $implodeOn,
            joinOn: $joinOn,
        );

        $result = $d->explode($in);

        self::assertEquals($expected, $result);
    }

    public function explosionExamples(): iterable
    {
        yield 'A single pair gets parsed' => [
            'implodeOn' => ',',
            'joinOn' => '=',
            'in' => 'beep=boop',
            'expected' => ['beep' => 'boop'],
        ];
        yield 'Multiple pairs get parsed' => [
            'implodeOn' => ',',
            'joinOn' => '=',
            'in' => 'beep=boop,narf=poink',
            'expected' => ['beep' => 'boop', 'narf' => 'poink'],
        ];
        yield 'Whitespace is trimmed for both keys and values' => [
            'implodeOn' => ',',
            'joinOn' => '=',
            'in' => 'beep = boop, narf = poink',
            'expected' => ['beep' => 'boop', 'narf' => 'poink'],
        ];
        yield 'Missing joiners result in an empty string value' => [
            'implodeOn' => ',',
            'joinOn' => '=',
            'in' => 'beep = boop, narf',
            'expected' => ['beep' => 'boop', 'narf' => '',],
        ];
        yield 'Trailing imploders are ignored' => [
            'implodeOn' => ',',
            'joinOn' => '=',
            'in' => 'beep = boop, narf = poink,',
            'expected' => ['beep' => 'boop', 'narf' => 'poink'],
        ];
        yield 'Extra joiners are ignored' => [
            'implodeOn' => ',',
            'joinOn' => '=',
            'in' => 'beep=boop, narf=poink=blurg,',
            'expected' => ['beep' => 'boop', 'narf' => 'poink'],
        ];
    }
}
