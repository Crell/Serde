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

    public function serializeInitialize(): mixed
    {
        return [];
    }

    public function serializeFinalize(mixed $runningValue): string
    {
        return json_encode($runningValue, JSON_THROW_ON_ERROR);
    }

    public function getRemainingData(mixed $source, array $used): mixed
    {
        return array_diff_key($source, array_flip($used));
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }
}
