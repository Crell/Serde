<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\ArrayFormatter;

class ArrayFormatterTest extends ArrayBasedFormatterTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->formatters = [new ArrayFormatter()];
        $this->format = 'array';
    }

    protected function arrayify(mixed $serialized): array
    {
        return $serialized;
    }

    protected function name_mangling_validate(mixed $serialized): void
    {
        self::assertEquals('Larry', $serialized['renamed']);
        self::assertEquals('value', $serialized['TOUPPER']);
        self::assertEquals('value', $serialized['tolower']);
        self::assertEquals('value', $serialized['beep_prefix']);
    }

    protected function flattening_validate(mixed $serialized): void
    {
        self::assertEquals('Larry', $serialized['first']);
        self::assertEquals('Garfield', $serialized['last']);
        self::assertEquals('A', $serialized['a']);
        self::assertEquals(2, $serialized['b']);
        self::assertEquals('C', $serialized['c']);
    }

    protected function custom_object_reader_validate(mixed $serialized): void
    {
        self::assertEquals('huge', $serialized['task']['name']);
        self::assertEquals('big', $serialized['task']['size']);
    }

    protected function custom_type_map_validate(mixed $serialized): void
    {
        self::assertEquals('huge', $serialized['task']['name']);
        self::assertEquals('big', $serialized['task']['size']);
    }

    protected function typemap_on_parent_class_validate(mixed $serialized): void
    {
        self::assertEquals(3, $serialized['aShape']['radius']);
        self::assertEquals('circle', $serialized['aShape']['shape']);
    }
}
