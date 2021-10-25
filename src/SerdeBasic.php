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

/**
 * An "empty" Serde instance with no configuration.
 *
 * This class comes with no handlers or formatters pre-configured. You
 * must provide all of them, in the order you desire.  Remember
 * that you will get better performance if you provide the same analyzer
 * instance to this class and to the handlers and formatters you inject,
 * as then they can share a cache.
 */
class SerdeBasic extends Serde
{
    /** @var PropertyReader[]  */
    protected readonly array $readers;

    /** @var PropertyWriter[] */
    protected readonly array $writers;

    /** @var Formatter[] */
    protected readonly array $formatters;

    /** @var Deformatter[] */
    protected readonly array $deformatters;

    /**
     * @param ClassAnalyzer $analyzer
     * @param array<int, PropertyReader|PropertyWriter> $handlers
     * @param array<int, Formatter|Deformatter> $formatters
     */
    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
        array $handlers = [],
        array $formatters = [],
    ) {
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
}
