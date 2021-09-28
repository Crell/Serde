<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

class JsonFormatter implements Formatter, Deformatter, SupportsCollecting
{
    use ArrayBasedFormatter;
    use ArrayBasedDeformatter;

    public function format(): string
    {
        return 'json';
    }

    public function serializeInitialize(): array
    {
        return [];
    }

    public function serializeFinalize(mixed $runningValue): string
    {
        return json_encode($runningValue, JSON_THROW_ON_ERROR);
    }

    public function deserializeInitialize(mixed $serialized): mixed
    {
        return json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }
}
