<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\Serde\AST\StructValue;
use Crell\Serde\AST\Value;
use Crell\Serde\Records\Point;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{
    /**
     * @test
     */
    public function stuff(): void
    {
        // Temporary to force autoloading.
        class_exists(Value::class);

        //$p = new Pipeline(source: new ObjectDecoder(), target: new ArrayEncoder());

        $subject = new ObjectDecoder(new Analyzer());

        $result = $subject->decode(new Point(3, 5, 9));

        self::assertInstanceOf(StructValue::class, $result);
        self::assertEquals(Point::class, $result->type);
        self::assertCount(3, $result->values);
    }


}
