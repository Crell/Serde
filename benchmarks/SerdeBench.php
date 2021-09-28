<?php

declare(strict_types=1);

namespace Crell\Serde\Benchmarks;

use Crell\Serde\Formatter\JsonFormatter;
use Crell\Serde\Records\Point;
use Crell\Serde\Serde;
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
    protected readonly Serde $serde;

    public function setUp(): void
    {
        $this->serde = new Serde(formatters: [new JsonFormatter()]);
    }

    public function tearDown(): void {}

    public function benchPoint(): void
    {
        $p1 = new Point(1, 2, 3);

        $json = $this->serde->serialize($p1, 'json');

        $result = $this->serde->deserialize($json, from: 'json', to: Point::class);
    }

}
