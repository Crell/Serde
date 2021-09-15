<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\RustSerializer;

trait RecursiveExtractorTrait
{
    protected RustSerializer $serializer;

    public function setSerializer(RustSerializer $serializer): static
    {
        $this->serializer = $serializer;
        return $this;
    }
}
