<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Records\EmptyData;
use Crell\Serde\Records\NullArrays;
use Crell\Serde\Records\Pagination\Product;
use Devium\Toml\Toml;
use Crell\Serde\Formatter\TomlFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

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

        $this->weakModeLists = Toml::encode([
            'seq' => [1, '2', 3],
            'dict' => ['a' => 1, 'b' => '2'],
        ]);
    }

    #[Test]
    #[DataProvider('round_trip_examples')]
    #[DataProvider('value_object_flatten_examples')]
    #[DataProvider('mixed_val_property_examples')]
    #[DataProvider('mixed_val_property_object_examples')]
    #[DataProvider('union_types_examples')]
    #[DataProvider('compound_types_examples')]
    public function round_trip(object $data): void
    {
        if ($this->dataName() === 'empty_values') {
            /** @var EmptyData $data */
            $s = new SerdeCommon(formatters: $this->formatters);

            $serialized = $s->serialize($data, $this->format);

            $this->validateSerialized($serialized, $this->dataName());

            /** @var EmptyData $result */
            $result = $s->deserialize($serialized, from: $this->format, to: $data::class);

            // Manually assert the fields that can transfer.
            // requiredNullable will be uninitialized for TOML, and
            // many others are supposed to be uninitialized, so don't check for them.
            self::assertEquals($data->required, $result->required);
            self::assertEquals($data->nonConstructorDefault, $result->nonConstructorDefault);
            self::assertEquals($data->nullable, $result->nullable);
            self::assertEquals($data->withDefault, $result->withDefault);

        } elseif ($this->dataName() === 'array_of_null_serializes_cleanly') {
            /** @var NullArrays $data */
            $s = new SerdeCommon(formatters: $this->formatters);

            $serialized = $s->serialize($data, $this->format);

            $this->validateSerialized($serialized, $this->dataName());

            /** @var NullArrays $result */
            $result = $s->deserialize($serialized, from: $this->format, to: $data::class);

            // TOML can't handle null values in arrays. So in this case,
            // we allow it to be empty.  In most cases this is good enough.
            // In the rare case where the null has significance, it's probably
            // a sign of a design flaw in the object to begin with.
            self::assertEmpty($result->arr);
        } else {
            // Defer back to the parent for the rest.
            parent::round_trip($data);
        }
    }

    #[Test]
    public function toml_float_strings_are_safe_in_strict(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $serialized = Toml::encode([
            'name' => 'beep',
            'price' => "3.14",
        ]);

        /** @var Product $result */
        $result = $s->deserialize($serialized, from: $this->format, to: Product::class);

        self::assertEquals('beep', $result->name);
        self::assertEquals('3.14', $result->price);
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
        self::assertArrayNotHasKey('roNullable', $toTest);
    }

    /**
     * On TOML, the array won't have nulls but will just be empty.
     */
    public function array_of_null_serializes_cleanly_validate(mixed $serialized): void
    {
        $toTest = $this->arrayify($serialized);

        self::assertEmpty($toTest['arr']);
    }

    protected function arrayify(mixed $serialized): array
    {
        return (array) Toml::decode($serialized, true, true);
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
            // This should NOT throw on TOML.
            if ($v['serialized'] === ['afloat' => '3.14']) {
                continue;
            }
            $v['serialized'] = Toml::encode($v['serialized']);
            yield $k => $v;
        }
    }
}
