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

    /**
     * Serialize an object to a given format.
     *
     * @param object $object
     *   The object to serialize.
     * @param string $format
     *   Identifier of the format to which to serialize.
     * @param mixed|null $init
     *   An initial value to serialize into. Its expected type and meaning will vary with the format.
     * @param array<string|null> $scopes
     *   An array of scopes that should be serialized.  Fields not in this scope will be ignored.
     * @return mixed
     *   The serialized value.
     */
    public function serialize(object $object, string $format, mixed $init = null, array $scopes = []): mixed
    {
        $formatter = $this->formatters[$format] ?? throw UnsupportedFormat::create($format, Direction::Serialize);

        $classDef = $this->analyzer->analyze($object, ClassSettings::class, scopes: $scopes);

        $runner = new Serializer(
            analyzer: $this->analyzer,
            exporters: $this->exporters,
            formatter: $formatter,
            typeMapper: $this->typeMapper,
            scopes: $scopes,
        );

        $rootField = $formatter->rootField($runner, $object::class);
        $init ??= $formatter->serializeInitialize($classDef, $rootField);

        $serializedValue = $runner->serialize($object, $init, $rootField);

        return $formatter->serializeFinalize($serializedValue, $classDef);
    }

    /**
     * Deserialize a value to a PHP object.
     *
     * @template T of object
     * @param mixed $serialized
     *   The serialized form to deserialize.
     * @param string $from
     *   The format the serialized value is in.
     * @param class-string<T> $to
     *   The class name of the class to which to deserialize.
     * @param array<string|null> $scopes
     *   An array of scopes that should be deserialized.  Fields not in this scope will be ignored.
     * @return T
     *   The deserialized object.
     */
    public function deserialize(mixed $serialized, string $from, string $to, array $scopes = []): object
    {
        $formatter = $this->deformatters[$from] ?? throw UnsupportedFormat::create($from, Direction::Deserialize);

        $runner = new Deserializer(
            analyzer: $this->analyzer,
            importers: $this->importers,
            deformatter: $formatter,
            typeMapper: $this->typeMapper,
            scopes: $scopes,
        );

        $classDef = $this->analyzer->analyze($to, ClassSettings::class, scopes: $scopes);

        $rootField = $formatter->rootField($runner, $to);
        $decoded = $formatter->deserializeInitialize($serialized, $classDef, $rootField, $runner);

        $new = $runner->deserialize($decoded, $rootField);

        $formatter->deserializeFinalize($decoded);

        return $new;
    }
}
