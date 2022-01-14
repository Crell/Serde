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
}
