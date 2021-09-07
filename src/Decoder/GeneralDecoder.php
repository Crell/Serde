<?php

declare(strict_types=1);

namespace Crell\Serde\Decoder;

use Crell\Serde\AST\Value;
use Crell\Serde\Decoder;
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
            use Deferer;

            public function decode(mixed $value): Value
            {
                throw ResourcePropertiesNotAllowed::create($value);
            }
        };

        foreach ($this->decoders as $decoder) {
            $decoder->setDeferrer($this);
        }

        $this->defaultDecoder->setDeferrer($this);
    }

    public function setDecoderFor(string $type, Decoder $decoder): static
    {
        $decoder->setDeferrer($this);
        $this->decoders[$type] = $decoder;
        return $this;
    }

    public function setDeferrer(Decoder $decoder): void
    {
        // Do nothing, because this class must be the root.
        // It is what is passed to other Decoders.
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
