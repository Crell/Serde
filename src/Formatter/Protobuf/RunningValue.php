<?php

namespace Crell\Serde\Formatter\Protobuf;

class RunningValue
{
    /**
     * @var array<int>
     */
    private array $fieldNumLocked = [];

    public function __construct(
        public string $bytes = '',
        public int $cursorOffset = 0,
        public int $lastFieldNum = 0,
    ) {}

    public function lockFieldNumber(): void {
        $this->fieldNumLocked[] = $this->lastFieldNum;
    }

    public function unlockFieldNumber(): void {
        $this->lastFieldNum = array_pop($this->fieldNumLocked) ?? throw new \LogicException('too many unlocks');
    }

    public function appendTag(ProtoWireType $type, int|null $fieldNum): void
    {
        $fieldNum ??= ($this->fieldNumLocked ? $this->lastFieldNum : ++$this->lastFieldNum);
        $this->lastFieldNum = $fieldNum;

        $tag = $type->tag($fieldNum);
        $this->appendVarInt($tag);
    }

    public function appendVarInt(int $value): void
    {
        $bytes = '';
        while ($value > 0x7f) {
            $bytes .= chr(($value & 0x7f) | 0x80);
            $value >>= 7;
        }
        $bytes .= chr($value & 0x7f);
        $this->bytes .= $bytes;
        $this->cursorOffset += strlen($bytes);
    }

    public function skipTag(): void
    {
        $this->lastFieldNum = $this->lastFieldNum + 1;
    }

    public function appendBytes(string $bytes): void
    {
        $this->bytes .= $bytes;
        $this->cursorOffset += strlen($bytes);
    }

    public function decodeVarInt(): int
    {
        $result = 0;
        $shift = 0;
        $len = strlen($this->bytes);
        while ($this->cursorOffset < $len) {
            $byte = ord($this->bytes[$this->cursorOffset++]);
            $result |= (($byte & 0x7f) << $shift);
            if (($byte & 0x80) === 0) {
                return $result;
            }
            $shift += 7;
            if ($shift >= 64) {
                throw new \RuntimeException("Varint is too large; possibly corrupted data");
            }
        }

        throw new \RuntimeException("Unexpected end of data while parsing varint");
    }
}
