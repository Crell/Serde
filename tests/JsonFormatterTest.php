<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\JsonFormatter;

class JsonFormatterTest extends SerdeTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->formatters = [new JsonFormatter()];
        $this->format = 'json';
    }

    protected function point_validate(mixed $serialized): void
    {
        self::assertEquals('{"x":1,"y":2,"z":3}', $serialized);
    }

    protected function visibility_validate(mixed $serialized): void
    {
        self::assertEquals('{"public":1,"protected":2,"private":3,"visibility":{"public":4,"protected":5,"private":6}}', $serialized);
    }

    protected function optional_point_validate(mixed $serialized): void
    {
        self::assertEquals('{"x":1,"y":2,"z":0}', $serialized);
    }

    protected function allFields_validate(mixed $serialized): void
    {
//        var_dump($serialized);
    }

    protected function name_mangling_validate(mixed $serialized): void
    {
        $toTest = json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('Larry', $toTest['renamed']);
        self::assertEquals('value', $toTest['TOUPPER']);
        self::assertEquals('value', $toTest['tolower']);
        self::assertEquals('value', $toTest['beep_prefix']);
    }

    protected function flattening_validate(mixed $serialized): void
    {
        $toTest = json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('Larry', $toTest['first']);
        self::assertEquals('Garfield', $toTest['last']);
        self::assertEquals('A', $toTest['a']);
        self::assertEquals(2, $toTest['b']);
        self::assertEquals('C', $toTest['c']);
    }


    protected function custom_object_reader_validate(mixed $serialized): void
    {
        $toTest = json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('huge', $toTest['task']['name']);
        self::assertEquals('big', $toTest['task']['size']);
    }

    protected function custom_type_map_validate(mixed $serialized): void
    {
        $toTest = json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('huge', $toTest['task']['name']);
        self::assertEquals('big', $toTest['task']['size']);
    }

    protected function typemap_on_parent_class_validate(mixed $serialized): void
    {
        $toTest = json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(3, $toTest['aShape']['radius']);
        self::assertEquals('circle', $toTest['aShape']['shape']);
    }
}
