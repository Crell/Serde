<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\Serde\AST\Value;
use Crell\Serde\Decoder\GeneralDecoder;
use Crell\Serde\Decoder\ObjectDecoder;
use Crell\Serde\Records\Point;
use PHPUnit\Framework\TestCase;
use function Crell\fp\pipe;

class MetadataTest extends TestCase
{

    /**
     * @test
     */
    public function stuff(): void
    {
        $schemaSource = new Analyzer();
        $subject = new Serializer($schemaSource);

        $subject->addTarget('object', new ObjectConverter());
        $subject->addTarget('json', new JsonConverter());

        $p = new Point(1, 2, 3);

        $subject->convert(subject: $p, from: 'object', to: 'array');

    }
}

class Serializer
{
    protected array $targets = [];

    protected array $transformers = [];

    public function __construct(public $schemaSource) {}

    public function addTarget(string $key, object $target): void
    {
        $this->targets[$key] = $target;
    }

    public function convert(mixed $subject, string $from, string $to): mixed
    {
        return pipe($subject,
            fn(mixed $s): Value => $this->targets[$from]->toAst($s, $this->schemaSource),
            array_map(fn(Transformer $transformer): callable => fn(Value $ast): Value => $transformer->transform($ast, $this->schemaSource), $this->transformers),
            fn(Value $ast): mixed => $this->targets[$to]->fromAst($ast, $this->schemaSource)
        );
    }
}

interface Transformer
{
    public function transform(Value $ast, object $schemaSource): Value;
}

interface Converter
{
    public function toAst(mixed $subject, object $schemaSource): Value;

    public function fromAst(Value $value, object $schemaSource): mixed;
}

class ObjectConverter implements Converter
{
    public function toAst(mixed $subject, object $schemaSource): Value
    {
        // @todo Rewrite this to use $schemaSource directly
        $decoder = new GeneralDecoder(new ObjectDecoder($schemaSource));
        return $decoder->decode($subject);
    }

    public function fromAst(Value $value, object $schemaSource): mixed
    {
        // TODO: Implement fromAst() method.
    }

}

class ArrayConverter implements Converter
{
    /**
     * @param array $subject
     * @param object $schemaSource
     * @return Value
     */
    public function toAst(mixed $subject, object $schemaSource): Value
    {
        foreach ($schemaSource->fields as $field) {

        }


        // TODO: Implement toAst() method.
    }

    public function fromAst(Value $value, object $schemaSource): mixed
    {
        // TODO: Implement fromAst() method.
    }

}
