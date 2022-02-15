<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
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
    ) {}

    public function format(): string
    {
        return 'yaml';
    }

    /**
     * @param ClassSettings $classDef
     * @param Field $rootField
     * @return array<string, mixed>
     */
    public function serializeInitialize(ClassSettings $classDef, Field $rootField): array
    {
        return ['root' => []];
    }

    public function serializeFinalize(mixed $runningValue, ClassSettings $classDef): string
    {
        return Yaml::dump($runningValue['root'], inline: $this->inline, indent: $this->indent, flags: $this->dumpFlags);
    }

    /**
     * @param mixed $serialized
     * @param Field $rootField
     * @return array<string, mixed>
     */
    public function deserializeInitialize(mixed $serialized, Field $rootField): array
    {
        return ['root' => Yaml::parse($serialized ?: '{}', $this->parseFlags)];
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }
}
