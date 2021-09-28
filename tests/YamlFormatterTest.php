<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\ArrayFormatter;
use Crell\Serde\Formatter\YamlFormatter;
use Symfony\Component\Yaml\Yaml;

class YamlFormatterTest extends ArrayBasedFormatterTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->formatters = [new YamlFormatter()];
        $this->format = 'yaml';
    }

    protected function arrayify(mixed $serialized): array
    {
        return Yaml::parse($serialized);
    }
}
