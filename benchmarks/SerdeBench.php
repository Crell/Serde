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
 * @Revs(10)
 * @Iterations(3)
 * @Warmup(2)
 * @BeforeMethods({"setUp"})
 * @AfterMethods({"tearDown"})
 * @OutputTimeUnit("milliseconds", precision=3)
 */
class SerdeBench
{
    public function setUp(): void {}

    public function tearDown(): void {}

    public function benchPoint(): void
    {
        $s = new Serde(formatters: [new JsonFormatter()]);

        $p1 = new Point(1, 2, 3);

        $json = $s->serialize($p1, 'json');

        $result = $s->deserialize($json, from: 'json', to: Point::class);
    }

}
