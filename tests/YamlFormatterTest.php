<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\YamlFormatter;
use Symfony\Component\Yaml\Yaml;

class YamlFormatterTest extends ArrayBasedFormatterTest
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

    }

    protected function arrayify(mixed $serialized): array
    {
        return Yaml::parse($serialized);
    }

    public function non_strict_properties_examples(): iterable
    {
        foreach ($this->non_strict_properties_examples_data() as $k => $v) {
            $v['serialized'] = Yaml::dump($v['serialized']);
            yield $k => $v;
        }
    }

    public function strict_mode_throws_examples(): iterable
    {
        foreach ($this->strict_mode_throws_examples_data() as $k => $v) {
            $v['serialized'] = Yaml::dump($v['serialized']);
            yield $k => $v;
        }
    }
}
