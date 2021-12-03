<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\PropertyHandler\PropertyReader;
use Crell\Serde\PropertyHandler\PropertyWriter;

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
 * Note: You MUST repeat the for readonly properties in the subclass,
 * exactly as defined here, or they will not be settable from the
 * subclass constructor.  This is a PHP limitation.
 */
abstract class Serde
{
    /** @var PropertyReader[]  */
    protected readonly array $readers;

    /** @var PropertyWriter[] */
    protected readonly array $writers;

    /** @var Formatter[] */
    protected readonly array $formatters;

    /** @var Deformatter[] */
    protected readonly array $deformatters;

    protected readonly ClassAnalyzer $analyzer;

    public function serialize(object $object, string $format): mixed
    {
        $formatter = $this->formatters[$format] ?? throw UnsupportedFormat::create($format, Direction::Serialize);

        $classDef = $this->analyzer->analyze($object, ClassDef::class);

        $init = $formatter->serializeInitialize($classDef);

        $inner = new Serializer(
            analyzer: $this->analyzer,
            readers: $this->readers,
            formatter: $formatter,
        );

        $serializedValue = $inner->serialize($object, $init, $formatter->initialField($object::class));

        return $formatter->serializeFinalize($serializedValue, $classDef);
    }

    public function deserialize(mixed $serialized, string $from, string $to): object
    {
        $formatter = $this->deformatters[$from] ?? throw UnsupportedFormat::create($from, Direction::Deserialize);

        $decoded = $formatter->deserializeInitialize($serialized);

        $inner = new Deserializer(
            analyzer: $this->analyzer,
            writers: $this->writers,
            deformatter: $formatter,
        );

        $new = $inner->deserialize($decoded, $formatter->initialField($to));

        $formatter->deserializeFinalize($decoded);

        return $new;
    }
}
