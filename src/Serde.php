<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\PropertyHandler\Exporter;
use Crell\Serde\PropertyHandler\Importer;

/**
 * Common base class for Serde executors.
 *
 * If you want to create a custom Serde configuration, extend
 * this class and hard-code whatever handlers and formatters are
 * appropriate.  You may make it further configurable via the
 * constructor if you wish.
 *
 * For most typical cases, you can use SerdeCommon and be happy.
 *
 * Note: You MUST repeat the five readonly properties in the subclass,
 * exactly as defined here, or they will not be settable from the
 * subclass constructor.  This is a PHP limitation.
 */
abstract class Serde
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

    protected readonly ClassAnalyzer $analyzer;

    public function serialize(object $object, string $format, mixed $init = null, array $scopes = []): mixed
    {
        $formatter = $this->formatters[$format] ?? throw UnsupportedFormat::create($format, Direction::Serialize);

        $classDef = $this->analyzer->analyze($object, ClassSettings::class, scopes: $scopes);

        $inner = new Serializer(
            analyzer: $this->analyzer,
            exporters: $this->exporters,
            formatter: $formatter,
            typeMapper: $this->typeMapper,
            scopes: $scopes,
        );

        $rootField = $formatter->rootField($inner, $object::class);
        $init ??= $formatter->serializeInitialize($classDef, $rootField);

        $serializedValue = $inner->serialize($object, $init, $rootField);

        return $formatter->serializeFinalize($serializedValue, $classDef);
    }

    public function deserialize(mixed $serialized, string $from, string $to, array $scopes = []): object
    {
        $formatter = $this->deformatters[$from] ?? throw UnsupportedFormat::create($from, Direction::Deserialize);

        $inner = new Deserializer(
            analyzer: $this->analyzer,
            importers: $this->importers,
            deformatter: $formatter,
            typeMapper: $this->typeMapper,
            scopes: $scopes,
        );

        $rootField = $formatter->rootField($inner, $to);
        $decoded = $formatter->deserializeInitialize($serialized, $rootField);

        $new = $inner->deserialize($decoded, $rootField);

        $formatter->deserializeFinalize($decoded);

        return $new;
    }
}
