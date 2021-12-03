<?php

declare(strict_types=1);

namespace Crell\Serde\Benchmarks;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\Formatter\JsonFormatter;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\BackedSize;
use Crell\Serde\Records\Point;
use Crell\Serde\Records\Size;
use Crell\Serde\SerdeCommon;
use PhpBench\Benchmark\Metadata\Annotations\AfterMethods;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\OutputTimeUnit;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;

/**
 * @Revs(100)
 * @Iterations(10)
 * @Warmup(2)
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 * @OutputTimeUnit("milliseconds", precision=3)
 */
class SerdeBench
{
    protected readonly SerdeCommon $serde;

    public function setUp(): void
    {
        $analyzer = new MemoryCacheAnalyzer(new Analyzer());
        $this->serde = new SerdeCommon(
            analyzer: $analyzer,
            formatters: [new JsonFormatter()]
        );
    }

    public function tearDown(): void {}

    public function benchPoint(): void
    {
        $p1 = new Point(1, 2, 3);

        $json = $this->serde->serialize($p1, 'json');

        $result = $this->serde->deserialize($json, from: 'json', to: Point::class);
    }

    public function benchAllFields(): void
    {
        $data = new AllFieldTypes(
            anint: 5,
            string: 'hello',
            afloat: 3.14,
            bool: true,
            dateTimeImmutable: new \DateTimeImmutable('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
            dateTime: new \DateTime('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
            dateTimeZone: new \DateTimeZone('America/Chicago'),
            simpleArray: ['a', 'b', 'c', 1, 2, 3],
            assocArray: ['a' => 'A', 'b' => 'B', 'c' => 'C'],
            simpleObject: new Point(4, 5, 6),
            objectList: [new Point(1, 2, 3), new Point(4, 5, 6)],
            nestedArray: [
                'a' => [1, 2, 3],
                'b' => ['a' => 1, 'b' => 2, 'c' => 3],
                'c' => 'normal',
            ],
            size: Size::Large,
            backedSize: BackedSize::Large,
        );

        $serialized = $this->serde->serialize($data, 'json');

        $result = $this->serde->deserialize($serialized, from: 'json', to: AllFieldTypes::class);
    }

}
