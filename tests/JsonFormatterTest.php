<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\JsonFormatter;

class JsonFormatterTest extends ArrayBasedFormatterTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->formatters = [new JsonFormatter()];
        $this->format = 'json';
        $this->emptyData = '{}';

        $this->aliasedData = json_encode([
            'un' => 1,
            'dos' => 'dos',
            'dot' => [
                'x' => 1,
                'y' => 2,
                'z' => 3,
            ]
        ], JSON_THROW_ON_ERROR);

        $this->invalidDictStringKey = '{"stringKey": {"a": "A", "2": "B"}, "intKey": {"5": "C", "d": "D"}}';

        $this->invalidDictIntKey = '{"stringKey": {"a": "A", "2": "B"}, "intKey": {"5": "C", "10": "D"}}';
    }

    protected function arrayify(mixed $serialized): array
    {
        return json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
    }

    protected function point_validate(mixed $serialized): void
    {
        parent::point_validate($serialized);

        self::assertEquals('{"x":1,"y":2,"z":3}', $serialized);
    }

    protected function visibility_validate(mixed $serialized): void
    {
        parent::visibility_validate($serialized);
        self::assertEquals('{"public":1,"protected":2,"private":3,"visibility":{"public":4,"protected":5,"private":6}}', $serialized);
    }

    protected function optional_point_validate(mixed $serialized): void
    {
        parent::optional_point_validate($serialized);
        self::assertEquals('{"x":1,"y":2,"z":0}', $serialized);
    }

    public function non_strict_properties_examples(): iterable
    {
        foreach ($this->non_strict_properties_examples_data() as $k => $v) {
            $v['serialized'] = json_encode($v['serialized'], JSON_THROW_ON_ERROR);
            yield $k => $v;
        }
    }

    public function strict_mode_throws_examples(): iterable
    {
        foreach ($this->strict_mode_throws_examples_data() as $k => $v) {
            $v['serialized'] = json_encode($v['serialized'], JSON_THROW_ON_ERROR);
            yield $k => $v;
        }
    }
}
