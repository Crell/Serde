<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\ArrayFormatter;
use Crell\Serde\PropertyHandler\EnumOnArrayImporter;
use Crell\Serde\Records\BackedSize;
use Crell\Serde\Records\LiteralEnums;
use Crell\Serde\Records\Size;

class ArrayFormatterTest extends ArrayBasedFormatterTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->formatters = [new ArrayFormatter()];
        $this->format = 'array';
        $this->emptyData = [];

        $this->aliasedData = [
            'un' => 1,
            'dos' => 'dos',
            'dot' => [
                'x' => 1,
                'y' => 2,
                'z' => 3,
            ]
        ];

        $this->invalidDictStringKey = [
            'stringKey' => ['a' => 'A', 2 => 'B'],
            // The 'd' key here is invalid and won't deserialize.
            'intKey' => [5 => 'C', 'd' => 'D'],
        ];

        $this->invalidDictIntKey = [
            // The 2 key here is invalid and won't deserialize.
            'stringKey' => ['a' => 'A', 2 => 'B'],
            'intKey' => [5 => 'C', 10 => 'D'],
        ];
    }

    protected function arrayify(mixed $serialized): array
    {
        return $serialized;
    }

    /**
     * @test
     */
    public function literal_enums(): void
    {
        $s = new SerdeCommon(handlers: [new EnumOnArrayImporter()], formatters: $this->formatters);

        $serialized = [
            'size' => Size::Medium,
            'backedSize' => BackedSize::Small,
        ];

        $result = $s->deserialize($serialized, from: 'array', to: LiteralEnums::class);

        $expected = new LiteralEnums(size: Size::Medium, backedSize: BackedSize::Small);

        self::assertEquals($expected, $result);
    }

    public function non_strict_properties_examples(): iterable
    {
        foreach ($this->non_strict_properties_examples_data() as $k => $v) {
            yield $k => $v;
        }
    }

    public function strict_mode_throws_examples(): iterable
    {
        foreach ($this->strict_mode_throws_examples_data() as $k => $v) {
            yield $k => $v;
        }
    }
}
