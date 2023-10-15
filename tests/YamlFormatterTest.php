<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\YamlFormatter;
use Symfony\Component\Yaml\Yaml;

class YamlFormatterTest extends ArrayBasedFormatterTestCases
{
    public function setUp(): void
    {
        parent::setUp();
        $this->formatters = [new YamlFormatter()];
        $this->format = 'yaml';
        $this->emptyData = '{}';

        $this->aliasedData = Yaml::dump([
            'un' => 1,
            'dos' => 'dos',
            'dot' => [
                'x' => 1,
                'y' => 2,
                'z' => 3,
            ]
        ]);

        $this->invalidDictStringKey = Yaml::dump([
            'stringKey' => ['a' => 'A', 2 => 'B'],
            // The 'd' key here is invalid and won't deserialize.
            'intKey' => [5 => 'C', 'd' => 'D'],
        ]);

        $this->invalidDictIntKey = Yaml::dump([
            // The 2 key here is invalid and won't deserialize.
            'stringKey' => ['a' => 'A', 2 => 'B'],
            'intKey' => [5 => 'C', 10 => 'D'],
        ]);

        $this->missingOptionalData = YAML::dump(['a' => 'A']);

        $this->dictsInSequenceShouldFail = YAML::dump([
            'strict' => ['a' => 'A', 'b' => 'B'],
            'nonstrict' => ['a' => 'A', 'b' => 'B'],
        ]);

        $this->dictsInSequenceShouldPass = YAML::dump([
            'strict' => ['A', 'B'],
            'nonstrict' => ['a' => 'A', 'b' => 'B'],
        ]);
    }

    protected function arrayify(mixed $serialized): array
    {
        return Yaml::parse($serialized);
    }

    public static function non_strict_properties_examples(): iterable
    {
        foreach (self::non_strict_properties_examples_data() as $k => $v) {
            $v['serialized'] = Yaml::dump($v['serialized']);
            yield $k => $v;
        }
    }

    public static function strict_mode_throws_examples(): iterable
    {
        foreach (self::strict_mode_throws_examples_data() as $k => $v) {
            $v['serialized'] = Yaml::dump($v['serialized']);
            yield $k => $v;
        }
    }
}
