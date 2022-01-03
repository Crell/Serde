<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\Formatter;
use Crell\Serde\PropertyHandler\PropertyReader;
use Crell\Serde\PropertyHandler\PropertyWriter;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;
use function Crell\fp\afilter;
use function Crell\fp\indexBy;
use function Crell\fp\method;
use function Crell\fp\pipe;
use function Crell\fp\typeIs;

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

    protected readonly TypeMapper $typeMapper;

    /**
     * @param ClassAnalyzer $analyzer
     * @param array<int, PropertyReader|PropertyWriter> $handlers
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

        $this->readers = array_filter($handlers, typeIs(PropertyReader::class));
        $this->writers = array_filter($handlers, typeIs(PropertyWriter::class));

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
