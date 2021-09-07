<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\AST\BooleanValue;
use Crell\Serde\AST\FloatValue;
use Crell\Serde\AST\IntegerValue;
use Crell\Serde\AST\StringValue;
use Crell\Serde\AST\StructValue;
use Crell\Serde\AST\Value;
use Crell\Serde\Decoder\DateTimeImmutableDecoder;

class ObjectDecoder implements Decoder
{
    /** @var array<class-string, Decoder> */
    protected array $classDecoders = [];

    public function __construct(protected ClassAnalyzer $analyzer)
    {
        $this->classDecoders[\DateTimeImmutable::class] = new DateTimeImmutableDecoder();
    }

    public function addDecoderForClass(string $class, Decoder $decoder): static
    {
        $this->classDecoders[$class] = $decoder;
    }

    public function decode(object $object): Value
    {
        // Allow overrides to trigger first.
        foreach ($this->classDecoders as $class => $decoder) {
            if ($object instanceof $class) {
                return $decoder->decode($object);
            }
        }

        // If there were no overrides, do the default decoding.
        return $this->defaultObjectDecoding($object);
    }

    protected function defaultObjectDecoding(object $object): StructValue
    {
        /** @var ClassDef $classDef */
        $classDef = $this->analyzer->analyze($object, ClassDef::class);

        // @todo I'm not sure how to make this nicely functional, since $rProp would
        // be needed in both the map and the filter portion.
        $rObject = new \ReflectionObject($object);

        $fields = $classDef->properties;

        $ret = new StructValue($classDef->fullName);

        foreach ($fields as $field) {
            $rProp = $rObject->getProperty($field->phpName);
            if (! $rProp->isInitialized($object)) {
                continue;
            }
            $value = $rProp->getValue($object);

            $ret->values[$field->name] = match ($field->phpType) {
                'int' => new IntegerValue($value),
                'float' => new FloatValue($value),
                'string' => new StringValue($value),
                'bool' => new BooleanValue($value),
                'array' => throw new \RuntimeException('TODO'),
                // Objects will have whatever their object type is, so just recurse on that.
                default => $this->decode($value),
            };
        }

        return $ret;
    }
}
