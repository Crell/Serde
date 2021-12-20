<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\JsonStreamFormatter;
use Crell\Serde\Records\Point;
use PHPUnit\Framework\TestCase;

class JsonStreamFormatterTest extends TestCase
{
    /**
     * @test
     * @dataProvider streamExamples()
     */
    public function stream_serialize(object $data): void
    {
        $s = new SerdeCommon(formatters: [new JsonStreamFormatter()]);

        // Use a temp stream as a placeholder.
        $init = fopen('php://temp/', 'wb');

        $result = $s->serialize($data, format: 'json-stream', init: $init);

        fseek($result, 0);
        $json = stream_get_contents($result);

        $deserialized = $s->deserialize($json, from: 'json', to: $data::class);

        self::assertEquals($data, $deserialized);
    }

    /**
     * @see stream_serialize()
     */
    public function streamExamples(): iterable
    {
        yield Point::class => [
            'data' => new Point(1, 2, 3),
        ];
    }
}
