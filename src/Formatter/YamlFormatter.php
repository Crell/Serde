<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Symfony\Component\Yaml\Yaml;

class YamlFormatter implements Formatter, Deformatter, SupportsCollecting
{
    use ArrayBasedFormatter;
    use ArrayBasedDeformatter;

    /**
     * Constructor parameters map directly to the Symfony YAML component's dump() and parse() methods.
     *
     * @see Yaml::dump()
     * @see Yaml::parse()
     *
     * @param int   $inline
     *   The level where you switch to inline YAML
     * @param int   $indent
     *   The amount of spaces to use for indentation of nested nodes
     * @param int   $dumpFlags
     *   A bit field of DUMP_* constants to customize the dumped YAML string
     * @param int   $parseFlags
     *   A bit field of PARSE_* constants to customize the YAML parser behavior
     */
    public function __construct(
        protected readonly int $inline = 2,
        protected readonly int $indent = 4,
        protected readonly int $dumpFlags = 0,
        protected readonly int $parseFlags = 0,
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    public function format(): string
    {
        return 'yaml';
    }

    public function serializeInitialize(): array
    {
        return ['root' => []];
    }

    public function serializeFinalize(mixed $runningValue): string
    {
        return Yaml::dump($runningValue['root'], inline: $this->inline, indent: $this->indent, flags: $this->dumpFlags);
    }

    public function deserializeInitialize(mixed $serialized): mixed
    {
        return ['root' => Yaml::parse($serialized, $this->parseFlags)];
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }

    protected function getAnalyzer(): ClassAnalyzer
    {
        return $this->analyzer;
    }
}
