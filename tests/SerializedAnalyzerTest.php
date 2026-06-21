<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\Serde\Analyzer\Attributes\Stuff;
use Crell\Serde\Analyzer\Records\Dummy;
use Crell\Serde\Analyzer\YamlFileAnalyzer;
use PHPUnit\Framework\TestCase;

class SerializedAnalyzerTest extends TestCase
{

    /**
     * @test
     */
    public function stuff(): void
    {
        $analyzer = new YamlFileAnalyzer('/tmp/yamlanalyzer');

        $attributeAnalyzer = new Analyzer();
        $classSettings = $attributeAnalyzer->analyze(Dummy::class, Stuff::class);

        $analyzer->save($classSettings, Dummy::class, Stuff::class);

        $result = $analyzer->analyze(Dummy::class, Stuff::class);

        self::assertEquals($classSettings, $result);
    }
}
