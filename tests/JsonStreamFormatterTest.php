<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\FormatterStream;
use Crell\Serde\Formatter\JsonStreamFormatter;
use Crell\Serde\Records\Point;
use Crell\Serde\Records\Visibility;
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
        $init = FormatterStream::new(fopen('php://temp/', 'wb'));

        $result = $s->serialize($data, format: 'json-stream', init: $init);

        fseek($result->stream, 0);
        $json = stream_get_contents($result->stream);

        //var_dump($json);

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

        yield Visibility::class => [
            'data' => new Visibility(1, 2, 3, new Visibility(4, 5, 6)),
        ];
    }
}
