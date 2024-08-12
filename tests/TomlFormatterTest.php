<?php

declare(strict_types=1);

namespace Crell\Serde;

use Devium\Toml\Toml;
use Crell\Serde\Formatter\TomlFormatter;

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

    protected function arrayify(mixed $serialized): array
    {
        return Toml::decode($serialized, true);
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
