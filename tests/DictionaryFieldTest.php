<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\DictionaryField;
use PHPUnit\Framework\TestCase;

class DictionaryFieldTest extends TestCase
{
    /**
     * @test
     * @dataProvider implosionExamples
     *
     * @param array<string, string> $in
     */
    public function implosion(string $implodeOn, string $joinOn, array $in, string $expected): void
    {
        $d = new DictionaryField(
            implodeOn: $implodeOn,
            joinOn: $joinOn,
        );

        $result = $d->implode($in);

        self::assertEquals($expected, $result);
    }

    public static function implosionExamples(): iterable
    {
        yield [
            'implodeOn' => ',',
            'joinOn' => '=',
            'in' => ['narf' => 'poink'],
            'expected' => 'narf=poink',
        ];
        yield [
            'implodeOn' => ',',
            'joinOn' => '=',
            'in' => ['narf' => 'poink', 'beep' => 'boop'],
            'expected' => 'narf=poink,beep=boop',
        ];
        yield [
            'implodeOn' => ',',
            'joinOn' => '=',
            'in' => ['narf', 'poink'],
            'expected' => '0=narf,1=poink',
        ];
    }

    /**
     * @test
     * @dataProvider explosionExamples
     *
     * @param array<string, string> $expected
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

    public static function explosionExamples(): iterable
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
            'expected' => ['beep' => 'boop', 'narf' => ''],
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
        yield 'Empty input gives empty array' => [
            'implodeOn' => ',',
            'joinOn' => '=',
            'in' => '',
            'expected' => [],
        ];
    }
}
