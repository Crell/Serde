<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\PropertyHandler\DateTimePropertyReader;
use Crell\Serde\PropertyHandler\DictionaryPropertyReader;
use Crell\Serde\PropertyHandler\EnumPropertyReader;
use Crell\Serde\PropertyHandler\ObjectPropertyReader;
use Crell\Serde\PropertyHandler\PropertyReader;
use Crell\Serde\PropertyHandler\PropertyWriter;
use Crell\Serde\PropertyHandler\ScalarPropertyReader;
use Crell\Serde\PropertyHandler\SequencePropertyReader;

class RustSerializer
{
    /** @var PropertyReader[]  */
    protected readonly array $readers;

    /** @var PropertyWriter[] */
    protected readonly array $writers;

    public function __construct(
        protected readonly ?ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
        /** array<int, PropertyReader|PropertyWriter> */
        array $handlers = []
    ) {
        // Slot any custom handlers in before the generic object reader.
        $handlers = [
            new ScalarPropertyReader(),
            new SequencePropertyReader(),
            new DictionaryPropertyReader(),
            new DateTimePropertyReader(),
            new EnumPropertyReader(),
            ...$handlers,
            new ObjectPropertyReader($this->analyzer),
        ];

        $this->readers = array_filter($handlers, static fn ($handler): bool => $handler instanceof PropertyReader);
        $this->writers = array_filter($handlers, static fn ($handler): bool => $handler instanceof PropertyWriter);
    }

    public function serialize(object $object, string $format): string
    {
        // @todo $format would get used here.
        $formatter = new JsonFormatter();

        $init = $formatter->initialize();

        $inner = new Serializer(
            analyzer: $this->analyzer,
            readers: $this->readers,
            writers: $this->writers,
            formatter: $formatter,
            format: $format,
        );

        $serializedValue = $inner->serialize($object, $init);

        return $formatter->finalize($serializedValue);
    }

    public function deserialize(string $serialized, string $from, string $to): object
    {
        $formatters['json'] = new JsonFormatter();
        $formatter = $formatters[$from];

        $decoded = $formatter->deserializeInitialize($serialized);

        $inner = new Deserializer(
            analyzer: $this->analyzer,
            readers: $this->readers,
            writers: $this->writers,
            formatter: $formatter,
            format: $from,
        );

        $new = $inner->deserialize($decoded, $to);

        $formatter->finalizeDeserialize($decoded);

        return $new;
    }
}
