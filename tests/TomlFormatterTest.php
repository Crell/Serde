<?php

declare(strict_types=1);

namespace Crell\Serde;

use Devium\Toml\Toml;
use Crell\Serde\Formatter\TomlFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

class TomlFormatterTest extends ArrayBasedFormatterTestCases
{
    public function setUp(): void
    {
        parent::setUp();
        $this->formatters = [new TomlFormatter()];
        $this->format = 'toml';
        $this->emptyData = '';

        $this->aliasedData = Toml::encode([
            'un' => 1,
            'dos' => 'dos',
            'dot' => [
                'x' => 1,
                'y' => 2,
                'z' => 3,
            ]
        ]);

        $this->invalidDictStringKey = Toml::encode([
            'stringKey' => ['a' => 'A', 2 => 'B'],
            // The 'd' key here is invalid and won't deserialize.
            'intKey' => [5 => 'C', 'd' => 'D'],
        ]);

        $this->invalidDictIntKey = Toml::encode([
            // The 2 key here is invalid and won't deserialize.
            'stringKey' => ['a' => 'A', 2 => 'B'],
            'intKey' => [5 => 'C', 10 => 'D'],
        ]);

        $this->missingOptionalData = Toml::encode(['a' => 'A']);

        $this->dictsInSequenceShouldFail = Toml::encode([
            'strict' => ['a' => 'A', 'b' => 'B'],
            'nonstrict' => ['a' => 'A', 'b' => 'B'],
        ]);

        $this->dictsInSequenceShouldPass = Toml::encode([
            'strict' => ['A', 'B'],
            'nonstrict' => ['a' => 'A', 'b' => 'B'],
        ]);
    }

    #[Test, DataProvider('strict_mode_throws_examples')]
    public function strict_mode_throws_correct_exception(mixed $serialized, string $errorField, string $expectedType, string $foundType): void
    {
        if ($expectedType === 'float' && $foundType === 'string') {
            $this->markTestSkipped("it's normal for TOML");
        }

        parent::strict_mode_throws_correct_exception($serialized, $errorField, $expectedType, $foundType);
    }

    #[Test, DataProvider('round_trip_examples')]
    public function round_trip(object $data, string $name): void
    {
        if (in_array($name, [
            'array_of_null_serializes_cleanly',
            'arrays_with_valid_scalar_values',
        ], true)) {
            $this->markTestSkipped("it's normal for TOML");
        }

        $s = new SerdeCommon(formatters: $this->formatters);

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, $name);

        $result = $s->deserialize($serialized, from: $this->format, to: $data::class);

        self::assertEquals($this->clearNullKeys($data), $this->clearNullKeys($result));
    }

    protected function clearNullKeys(object $data): object
    {
        $obj = new stdClass();
        foreach (array_keys(get_object_vars($data)) as $key) {
            if ($data->{$key} !== null) {
                $obj->{$key} = $data->{$key};
            }
        }

        return $obj;
    }

    protected function empty_values_validate(mixed $serialized): void
    {
        $toTest = $this->arrayify($serialized);

        self::assertEquals('narf', $toTest['nonConstructorDefault']);
        self::assertEquals('beep', $toTest['required']);
        self::assertArrayNotHasKey('requiredNullable', $toTest);
        self::assertEquals('boop', $toTest['withDefault']);
        self::assertArrayNotHasKey('nullableUninitialized', $toTest);
        self::assertArrayNotHasKey('uninitialized', $toTest);
        self::assertNull($toTest['roNullable']);
    }

    protected function arrayify(mixed $serialized): array
    {
        return (array) Toml::decode($serialized, true);
    }

    public static function non_strict_properties_examples(): iterable
    {
        foreach (self::non_strict_properties_examples_data() as $k => $v) {
            $v['serialized'] = Toml::encode($v['serialized']);
            yield $k => $v;
        }
    }

    public static function strict_mode_throws_examples(): iterable
    {
        foreach (self::strict_mode_throws_examples_data() as $k => $v) {
            $v['serialized'] = Toml::encode($v['serialized']);
            yield $k => $v;
        }
    }
}
