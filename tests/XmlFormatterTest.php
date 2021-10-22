<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Formatter\XmlFormatter;

class XmlFormatterTest extends SerdeTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->formatters = [new XmlFormatter()];
        $this->format = 'xml';
    }
}
