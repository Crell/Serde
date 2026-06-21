<?php

declare(strict_types=1);

namespace Crell\Serde\Analyzer;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Serde;
use Crell\Serde\SerdeCommon;
use Symfony\Component\Yaml\Yaml;

/**
 * @todo Instead of a cache-style directory, let callers specify the exact file to read.
 * The intent isn't to use as a cache, but to let people hand-write YAML files instead of
 * using attributes.
 */
class YamlFileAnalyzer implements ClassAnalyzer
{
    private readonly string $directory;

    public function __construct(
        string $directory,
        private readonly Serde $serde = new SerdeCommon(new Analyzer()),
    ) {
        $this->directory = rtrim($directory, '/\\');
    }

    public function save(object $data, string $class, string $attribute, array $scopes = []): void
    {
        $yaml = $this->serde->serialize($data, format: 'yaml');

        $filename = $this->getFileName($class, $attribute, $scopes);

        $this->ensureDirectory($filename);

        file_put_contents($filename, $yaml);
    }

    private function ensureDirectory(string $filename): void
    {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
        }
    }

    private function getFileName(string $class, string $attribute, array $scopes): string
    {
        return $this->directory
            . DIRECTORY_SEPARATOR
            . str_replace('\\', '_', $attribute)
            . DIRECTORY_SEPARATOR
            . str_replace('\\', '_', $class)
            . DIRECTORY_SEPARATOR
            . (implode('_', $scopes) ?: 'all_scopes')
            . '.yaml';
    }

    public function analyze(object|string $class, string $attribute, array $scopes = []): object
    {
        // Everything is easier if we normalize to a class first.
        // Because anon classes have generated internal class names, they work, too.
        $class = is_string($class) ? $class : $class::class;

        $classFile = $this->getFileName($class, $attribute, $scopes);

        $yaml = Yaml::parseFile($classFile);

        $result = $this->serde->deserialize($yaml, from: 'array', to: $attribute);

        return $result;
    }
}
