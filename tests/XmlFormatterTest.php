<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\XmlFormatter;
use Crell\Serde\Formatter\XmlParserDeformatter;

class XmlFormatterTest extends SerdeTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->formatters = [new XmlFormatter(), new XmlParserDeformatter()];
        $this->format = 'xml';
    }
}
