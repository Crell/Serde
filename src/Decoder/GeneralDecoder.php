<?php

declare(strict_types=1);

namespace Crell\Serde\Decoder;

use Crell\Serde\AST\Value;
use Crell\Serde\Decoder;
use Crell\Serde\Deferrable;
use Crell\Serde\ResourcePropertiesNotAllowed;

class GeneralDecoder implements Decoder
{
    /** @var array<string, Decoder>  */
    protected array $decoders = [];

    public function __construct(
        protected Decoder $defaultDecoder,
    ) {
        $this->decoders['int'] = new IntegerDecoder();
        $this->decoders['float'] = new FloatDecoder();
        $this->decoders['string'] = new StringDecoder();
        $this->decoders['bool'] = new BooleanDecoder();
        $this->decoders['array'] = new ArrayDecoder();
        $this->decoders['DateTime'] = new DateTimeDecoder();
        $this->decoders['DateTimeImmutable'] = new DateTimeImmutableDecoder();

        $this->decoders['resource'] = new class implements Decoder {
            public function decode(mixed $value): Value
            {
                throw ResourcePropertiesNotAllowed::create($value);
            }
        };

        // @todo Convert to a splat array once we require 8.1.
        foreach (array_merge($this->decoders, [$this->defaultDecoder]) as $decoder) {
            if ($decoder instanceof Deferrable) {
                $decoder->setDeferrer($this);
            }
        }
    }

    public function encode(Value $ast): mixed
    {

    }

    public function setDecoderFor(string $type, Decoder $decoder): static
    {
        if ($decoder instanceof Deferrable) {
            $decoder->setDeferrer($this);
        }

        $this->decoders[$type] = $decoder;
        return $this;
    }

    public function decode(mixed $value): Value
    {
        $type = get_debug_type($value);

        if (str_starts_with('resource', $type)) {
            $type = 'resource';
        }

        $decoder = $this->decoders[$type] ?? $this->defaultDecoder;

        return $decoder->decode($value);
    }

}
