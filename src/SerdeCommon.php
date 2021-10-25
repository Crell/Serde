<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\PropertyHandler\DateTimePropertyReader;
use Crell\Serde\PropertyHandler\DateTimeZonePropertyReader;
use Crell\Serde\PropertyHandler\DictionaryPropertyReader;
use Crell\Serde\PropertyHandler\EnumPropertyReader;
use Crell\Serde\PropertyHandler\NativeSerializePropertyReader;
use Crell\Serde\PropertyHandler\ObjectPropertyReader;
use Crell\Serde\PropertyHandler\PropertyReader;
use Crell\Serde\PropertyHandler\PropertyWriter;
use Crell\Serde\PropertyHandler\ScalarPropertyReader;
use Crell\Serde\PropertyHandler\SequencePropertyReader;
use function Crell\fp\afilter;
use function Crell\fp\indexBy;
use function Crell\fp\pipe;

class SerdeCommon
{
    /** @var PropertyReader[]  */
    protected readonly array $readers;

    /** @var PropertyWriter[] */
    protected readonly array $writers;

    /** @var Formatter[] */
    protected readonly array $formatters;

    /** @var Deformatter[] */
    protected readonly array $deformatters;

    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
        /** array<int, PropertyReader|PropertyWriter> */
        array $handlers = [],
        /** array<Formatter|Deformatter> */
        array $formatters = [],
    ) {
        // Slot any custom handlers in before the generic object reader.
        $handlers = [
            new ScalarPropertyReader(),
            new SequencePropertyReader(),
            new DictionaryPropertyReader(),
            new DateTimePropertyReader(),
            new DateTimeZonePropertyReader(),
            new EnumPropertyReader(),
            ...$handlers,
            new NativeSerializePropertyReader($this->analyzer),
            new ObjectPropertyReader($this->analyzer),
        ];

        $this->readers = array_filter($handlers, static fn ($handler): bool => $handler instanceof PropertyReader);
        $this->writers = array_filter($handlers, static fn ($handler): bool => $handler instanceof PropertyWriter);

        $this->formatters = pipe(
            $formatters,
            afilter(static fn ($formatter): bool => $formatter instanceof Formatter),
            indexBy(static fn (Formatter $formatter): string => $formatter->format()),
        );

        $this->deformatters = pipe(
            $formatters,
            afilter(static fn ($formatter): bool => $formatter instanceof Deformatter),
            indexBy(static fn (Deformatter $formatter): string => $formatter->format()),
        );
    }

    public function serialize(object $object, string $format): mixed
    {
        $formatter = $this->formatters[$format] ?? throw UnsupportedFormat::create($format, Direction::Serialize);

        $init = $formatter->serializeInitialize();

        $inner = new Serializer(
            analyzer: $this->analyzer,
            readers: $this->readers,
            writers: $this->writers,
            formatter: $formatter,
        );

        $serializedValue = $inner->serialize($object, $init, $formatter->initialField($object::class));

        return $formatter->serializeFinalize($serializedValue);
    }

    public function deserialize(mixed $serialized, string $from, string $to): object
    {
        $formatter = $this->deformatters[$from] ?? throw UnsupportedFormat::create($from, Direction::Deserialize);

        $decoded = $formatter->deserializeInitialize($serialized);

        $inner = new Deserializer(
            analyzer: $this->analyzer,
            readers: $this->readers,
            writers: $this->writers,
            formatter: $formatter,
        );

        $new = $inner->deserialize($decoded, $formatter->initialField($to));

        $formatter->deserializeFinalize($decoded);

        return $new;
    }
}
