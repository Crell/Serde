<?php

declare(strict_types=1);

namespace Crell\Serde\Extractor;

use Crell\Serde\RustSerializer;

// @todo I hate this name, but I need a general name for extractors and injectors to say RecursiveThing.
interface SerializerAware
{
    public function setSerializer(RustSerializer $serializer): static;
}
