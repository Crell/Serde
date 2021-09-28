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
}
