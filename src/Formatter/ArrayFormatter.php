<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;

class ArrayFormatter implements Formatter, Deformatter, SupportsCollecting
{
    use ArrayBasedFormatter;
    use ArrayBasedDeformatter;

    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    public function format(): string
    {
        return 'array';
    }

    public function serializeInitialize(): array
    {
        return ['root' => []];
    }

    public function serializeFinalize(mixed $runningValue): mixed
    {
        return $runningValue['root'];
    }

    public function getRemainingData(mixed $source, array $used): array
    {
        return array_diff_key($source, array_flip($used));
    }

    public function deserializeInitialize(mixed $serialized): mixed
    {
        return ['root' => $serialized];
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }

    protected function getAnalyzer(): ClassAnalyzer
    {
        return $this->analyzer;
    }
}
