<?php

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\Binary;
use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\Fixed32;
use Crell\Serde\Attributes\Fixed64;
use Crell\Serde\Attributes\Float32;
use Crell\Serde\Attributes\Float64;
use Crell\Serde\Attributes\Int32;
use Crell\Serde\Attributes\Int64;
use Crell\Serde\Attributes\SFixed32;
use Crell\Serde\Attributes\SFixed64;
use Crell\Serde\Attributes\SInt32;
use Crell\Serde\Attributes\SInt64;
use Crell\Serde\Attributes\UInt32;
use Crell\Serde\Attributes\UInt64;
use Crell\Serde\Dict;
use Crell\Serde\Formatter\Protobuf\ProtoWireType;
use Crell\Serde\Formatter\Protobuf\RunningValue;
use Crell\Serde\Sequence;
use Crell\Serde\Serializer;

class ProtobufFormatter implements Formatter
{
    private const FULL32 = 0xffffffff;
    private const INT32_MAX = '4294967296';
    private static Field|null $root = null;

    public function format(): string
    {
        return 'protobuf';
    }

    public function rootField(Serializer $serializer, string $type): Field
    {
        return self::$root ??= Field::create('root', $type);
    }

    public function serializeInitialize(ClassSettings $classDef, Field $rootField): RunningValue
    {
        return new RunningValue();
    }

    public function serializeFinalize(mixed $runningValue, ClassSettings $classDef): string
    {
        assert($runningValue instanceof RunningValue);
        return $runningValue->bytes;
    }

    public function serializeFloat(mixed $runningValue, Field $field, ?float $next): RunningValue
    {
        assert($runningValue instanceof RunningValue);
        if ($next === null) {
            $runningValue->skipTag();
            return $runningValue;
        }

        $typeField = $field->typeField === null ? null : get_class($field->typeField);
        switch ($typeField) {
            case null:
            case Float64::class:
                $bytes = pack('e', $next);
                $runningValue->appendTag(ProtoWireType::Double, $field->fieldNum);
                $runningValue->appendBytes($bytes);
                break;
            case Float32::class:
                $bytes = pack('g', $next);
                $runningValue->appendTag(ProtoWireType::Single, $field->fieldNum);
                $runningValue->appendBytes($bytes);
                break;
        }

        return $runningValue;
    }

    public function serializeString(mixed $runningValue, Field $field, ?string $next): mixed
    {
        assert($runningValue instanceof RunningValue);

        if ($next === null) {
            $runningValue->skipTag();
            return $runningValue;
        }

        $typeField = $field->typeField === null ? null : get_class($field->typeField);
        switch ($typeField) {
            case null:
                if(!mb_check_encoding($next, 'UTF-8')) {
                    throw new \RuntimeException('Cannot encode non-utf8 string as protobuf');
                }
                // fall through on purpose.
            case Binary::class:
                $runningValue->appendTag(ProtoWireType::LengthDelimited, $field->fieldNum);
                $runningValue->appendVarInt(strlen($next));
                $runningValue->appendBytes($next);
                break;
            case Fixed32::class:
                if (extension_loaded('bcmath')) {
                    $bytes = bcmod($next, self::INT32_MAX);
                } else {
                    if (extension_loaded('gmp')) {
                        $bytes = gmp_mod($next, self::INT32_MAX);
                        $bytes = gmp_intval($bytes);
                    } else {
                        throw new \RuntimeException('GMP or bcmath extension required for string maths');
                    }
                }
                $bytes = (int)$bytes;
                return $this->serializeInt($runningValue, $field, $bytes);
            case Fixed64::class:
                if ($next[0] === '-') {
                    throw new \RuntimeException('Fixed64 requires a non-negative integer');
                }
                if (extension_loaded('bcmath')) {
                    $lo = (int)bcmod($next, self::INT32_MAX);
                    $hi = (int)bcdiv($next, self::INT32_MAX);
                    $bytes = pack('V2', $lo, $hi);
                } else {
                    if (extension_loaded('gmp')) {
                        $lo = gmp_intval(gmp_mod($next, self::INT32_MAX));
                        $hi = gmp_intval(gmp_div($next, self::INT32_MAX));
                        $bytes = pack('V2', $lo, $hi);
                    } else {
                        throw new \RuntimeException('GMP or bcmath extension required for string maths');
                    }
                }
                $runningValue->appendTag(ProtoWireType::Double, $field->fieldNum);
                $runningValue->appendBytes($bytes);
                break;
            case Int64::class:
                $negative = $next[0] === '-';
                $bytes = '';
                $value = $next;
                if (extension_loaded('bcmath')) {
                    if ($negative) {
                        $value = bcadd(bcpow('2', '64'), $value);
                    }
                    while (bccomp($value, '127') > 0) {
                        $byte = bcadd(bcmod($value, '128'), '128');
                        $bytes .= chr((int)$byte);
                        $value = bcdiv($value, '128', 0);
                    }
                    $bytes .= chr((int)$value);
                } else {
                    if (extension_loaded('gmp')) {
                        $value = gmp_init($value);
                        if ($negative) {
                            $value = gmp_add(gmp_pow('2', 64), $value);
                        }
                        while (gmp_cmp($value, '127') > 0) {
                            $byte = gmp_intval(gmp_add(gmp_mod($value, '128'), '128'));
                            $bytes .= chr($byte);
                            $value = gmp_div_q($value, '128');
                        }
                        $bytes .= chr(gmp_intval($value));
                    } else {
                        throw new \RuntimeException('GMP or bcmath extension required for string maths');
                    }
                }
                $runningValue->appendTag(ProtoWireType::VarInt, $field->fieldNum);
                $runningValue->appendBytes($bytes);
                break;
            case SFixed64::class:
                $negative = $next[0] === '-';
                if (extension_loaded('bcmath')) {
                    if ($negative) {
                        $next = bcadd(bcpow('2', '64'), $next);
                    }
                    $lo = (int)bcmod($next, self::INT32_MAX);
                    $hi = (int)bcdiv($next, self::INT32_MAX);
                    $bytes = pack('V2', $lo, $hi);
                } else {
                    if (extension_loaded('gmp')) {
                        $next = gmp_init($next);
                        if (gmp_sign($next) === -1) {
                            $next = gmp_add(gmp_pow('2', 64), $next);
                        }
                        $lo = gmp_intval(gmp_mod($next, self::INT32_MAX));
                        $hi = gmp_intval(gmp_div_q($next, self::INT32_MAX));
                        $bytes = pack('V2', $lo, $hi);
                    } else {
                        throw new \RuntimeException('GMP or bcmath extension required for string maths');
                    }
                }
                $runningValue->appendTag(ProtoWireType::Double, $field->fieldNum);
                $runningValue->appendBytes($bytes);
                break;
            case UInt32::class:
            case UInt64::class:
                $negative = $next[0] === '-';
                if ($negative) {
                    throw new \RuntimeException('UInt32 requires a non-negative integer');
                }
                $bytes = '';
                if (extension_loaded('bcmath')) {
                    $next = bcmod($next, self::INT32_MAX);
                    while (bccomp($next, '127') > 0) {
                        $byte = bcadd(bcmod($next, '128'), '128');
                        $bytes .= chr((int)$byte);
                        $next = bcdiv($next, '128', 0);
                    }
                    $bytes .= chr((int)$next);
                } else {
                    if (extension_loaded('gmp')) {
                        $next = gmp_init($next);
                        while (gmp_cmp($next, '127') > 0) {
                            $byte = gmp_add(gmp_mod($next, '128'), '128');
                            $bytes .= chr(gmp_intval($byte));
                            $next = gmp_div_q($next, '128');
                        }
                        $bytes .= chr(gmp_intval($next));
                    } else {
                        throw new \RuntimeException('GMP or bcmath extension required for string maths');
                    }
                }
                $runningValue->appendTag(ProtoWireType::VarInt, $field->fieldNum);
                $runningValue->appendBytes($bytes);
                break;
        }

        return $runningValue;
    }

    public function serializeInt(mixed $runningValue, Field $field, ?int $next): RunningValue
    {
        assert($runningValue instanceof RunningValue);

        if ($next === null) {
            $runningValue->skipTag();
            return $runningValue;
        }

        $typeField = $field->typeField === null ? null : get_class($field->typeField);

        switch ($typeField) {
            case Int32::class:
            case UInt32::class:
                $next = $next & self::FULL32;
            // fall through on purpose after truncating to 32-bit
            case null:
            case Int64::class:
            case UInt64::class:
                $runningValue->appendTag(ProtoWireType::VarInt, $field->fieldNum);
                $runningValue->appendVarInt($next);
                break;
            case Fixed32::class:
            case SFixed32::class:
                $runningValue->appendTag(ProtoWireType::Single, $field->fieldNum);
                $bytes = $next & self::FULL32;
                $bytes = pack('V', $bytes);
                $runningValue->appendBytes($bytes);
                break;
            case SInt32::class:
                $bytes = (($next << 1) ^ ($next >> 31)) & self::FULL32;
                $runningValue->appendTag(ProtoWireType::VarInt, $field->fieldNum);
                $runningValue->appendVarInt($bytes);
                break;
            case SInt64::class:
                $bytes = ($next << 1) ^ ($next >> 63);
                $runningValue->appendTag(ProtoWireType::VarInt, $field->fieldNum);
                $runningValue->appendVarInt($bytes);
                break;
            case Fixed64::class:
            case SFixed64::class:
                $runningValue->appendTag(ProtoWireType::Double, $field->fieldNum);
                $lo = $next & self::FULL32;
                $hi = ($next >> 32) & self::FULL32;
                $bytes = pack('V2', $lo, $hi);
                $runningValue->appendBytes($bytes);
                break;
        }

        return $runningValue;
    }

    public function serializeBool(mixed $runningValue, Field $field, ?bool $next): RunningValue
    {
        assert($runningValue instanceof RunningValue);
        if ($next === null) {
            $runningValue->skipTag();
            return $runningValue;
        }

        $runningValue->appendTag(ProtoWireType::VarInt, $field->fieldNum);
        $runningValue->appendVarInt($next ? 1 : 0);

        return $runningValue;
    }

    public function serializeSequence(mixed $runningValue, Field $field, Sequence $next, Serializer $serializer): RunningValue
    {
        assert($runningValue instanceof RunningValue);
        $runningValue->lockFieldNumber();
        foreach($next->items as $nextItem) {
            $serializer->serialize($nextItem->value, $runningValue, $nextItem->field);
        }
        $runningValue->unlockFieldNumber();
        return $runningValue;
    }

    public function serializeDictionary(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): RunningValue
    {
        assert($runningValue instanceof RunningValue);

        foreach($next->items as $item) {
            $map = new RunningValue();
            // field 1 is key
            $key = Field::create($item->field->serializedName, 'string');
            $serializer->serialize($item->field->serializedName, $map, $key);

            // field 2 is value
            $serializer->serialize($item->value, $map, $item->field->with(fieldNum: 2));

            // prepend to current value
            $runningValue->appendTag(ProtoWireType::LengthDelimited, $field->fieldNum);
            $bytes = $map->bytes;
            $runningValue->appendVarInt(strlen($bytes));
            $runningValue->appendBytes($bytes);
        }

        return $runningValue;
    }

    public function serializeObject(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): mixed
    {
        $buffer = new RunningValue();

        foreach($next->items as $nextItem) {
            $serializer->serialize($nextItem->value, $buffer, $nextItem->field);
        }

        if($field !== self::$root) {
            $runningValue->appendTag(ProtoWireType::LengthDelimited, $field->fieldNum);
            $runningValue->appendVarInt(strlen($buffer->bytes));
            $runningValue->appendBytes($buffer->bytes);
        } else {
            $runningValue->appendBytes($buffer->bytes);
        }

        return $runningValue;
    }

    public function serializeNull(mixed $runningValue, Field $field, mixed $next): RunningValue
    {
        assert($runningValue instanceof RunningValue);
        $runningValue->skipTag();
        return $runningValue;
    }
}
