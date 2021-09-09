<?php

declare(strict_types=1);

namespace Crell\Serde\Decoder;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\AST\StructValue;
use Crell\Serde\AST\Value;
use Crell\Serde\ClassDef;
use Crell\Serde\Decoder;
use Crell\Serde\Delegatable;

class ObjectDecoder implements Decoder, Delegatable
{
    use Delegator;

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

    /**
     * @param object $value
     * @return StructValue
     */
    public function decode(mixed $value): Value
    {
        // Allow overrides to trigger first.
        foreach ($this->classDecoders as $class => $decoder) {
            if ($value instanceof $class) {
                return $decoder->decode($value);
            }
        }

        // If there were no overrides, do the default decoding.
        return $this->defaultObjectDecoding($value);
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

            $ret->values[$field->name] = $this->deferrer->decode($value);
        }

        return $ret;
    }
}
