<?php

namespace Crell\Serde\Formatter\Protobuf;

enum ProtoWireType: int {
    case VarInt = 0;
    case Double = 1;
    case LengthDelimited = 2;
    // 3, 4 deprecated; not supported
    case Single = 5;

    public function tag(int $fieldNum): int {
        $fieldNum = $fieldNum << 3;
        $tag = $this->value;
        return $fieldNum | $tag;
    }
}
