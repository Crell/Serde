<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\Formatter\ArrayFormatter;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\Formatter\JsonFormatter;
use Crell\Serde\Formatter\YamlFormatter;
use Crell\Serde\PropertyHandler\DateTimeExporter;
use Crell\Serde\PropertyHandler\DateTimeZoneExporter;
use Crell\Serde\PropertyHandler\DictionaryExporter;
use Crell\Serde\PropertyHandler\EnumExporter;
use Crell\Serde\PropertyHandler\Exporter;
use Crell\Serde\PropertyHandler\Importer;
use Crell\Serde\PropertyHandler\NativeSerializeExporter;
use Crell\Serde\PropertyHandler\ObjectExporter;
use Crell\Serde\PropertyHandler\ObjectImporter;
use Crell\Serde\PropertyHandler\ScalarExporter;
use Crell\Serde\PropertyHandler\SequenceExporter;
use Symfony\Component\Yaml\Yaml;
use function Crell\fp\afilter;
use function Crell\fp\indexBy;
use function Crell\fp\method;
use function Crell\fp\pipe;
use function Crell\fp\typeIs;

/**
 * All-in serializer for most common cases.
 *
 * If you're not sure what to do, use this class. It comes pre-loaded
 * with all standard readers, writers, and formatters, but you can also
 * provide additional ones as needed.  In most cases you will only need
 * to provide an analyzer instance, or just accept the default.
 */
class SerdeCommon extends Serde
{
    /** @var Exporter[]  */
    protected readonly array $exporters;

    /** @var Importer[] */
    protected readonly array $importers;

    /** @var Formatter[] */
    protected readonly array $formatters;

    /** @var Deformatter[] */
    protected readonly array $deformatters;

    protected readonly TypeMapper $typeMapper;

    /**
     * @param ClassAnalyzer $analyzer
     * @param array<int, Exporter|Importer> $handlers
     * @param array<int, Formatter|Deformatter> $formatters
     * @param array<class-string, TypeMap> $typeMaps
     */
    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
        array $handlers = [],
        array $formatters = [],
        array $typeMaps = [],
    ) {
        $this->typeMapper = new TypeMapper($typeMaps, $this->analyzer);

        // Slot any custom handlers in before the generic object reader.
        $handlers = [
            new ScalarExporter(),
            new SequenceExporter(),
            new DictionaryExporter(),
            new DateTimeExporter(),
            new DateTimeZoneExporter(),
            ...$handlers,
            new EnumExporter(),
            new NativeSerializeExporter(),
            new ObjectExporter(),
            new ObjectImporter(),
        ];

        // Add the common formatters.
        $formatters[] = new JsonFormatter();
        $formatters[] = new ArrayFormatter();
        if (class_exists(Yaml::class)) {
            $formatters[] = new YamlFormatter();
        }

        // These lines by definition filter the array to the correct type, but
        // PHPStan doesn't know that.
        // @phpstan-ignore-next-line
        $this->exporters = array_filter($handlers, typeIs(Exporter::class));
        // @phpstan-ignore-next-line
        $this->importers = array_filter($handlers, typeIs(Importer::class));

        $this->formatters = pipe(
            $formatters,
            afilter(typeIs(Formatter::class)),
            indexBy(method('format')),
        );

        $this->deformatters = pipe(
            $formatters,
            afilter(typeIs(Deformatter::class)),
            indexBy(method('format')),
        );
    }
}
