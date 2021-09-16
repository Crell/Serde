<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\RustSerializer;

trait RecursivePropertyHandler
{
    protected RustSerializer $serializer;

    public function setSerializer(RustSerializer $serializer): static
    {
        $this->serializer = $serializer;
        return $this;
    }
}
