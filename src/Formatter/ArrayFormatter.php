<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

class ArrayFormatter implements Formatter, Deformatter, SupportsCollecting
{
    use ArrayBasedFormatter;
    use ArrayBasedDeformatter;

    public function format(): string
    {
        return 'array';
    }

    public function serializeInitialize(): mixed
    {
        return [];
    }

    public function serializeFinalize(mixed $runningValue): mixed
    {
        return $runningValue;
    }

    public function getRemainingData(mixed $source, array $used): mixed
    {
        return array_diff_key($source, array_flip($used));
    }

    public function deserializeInitialize(mixed $serialized): mixed
    {
        return $serialized;
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }
}
