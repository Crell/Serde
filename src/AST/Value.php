<?php

declare(strict_types=1);

namespace Crell\Serde\AST;

interface Value
{
}

interface PrimitiveValue extends Value {}

class IntegerValue implements PrimitiveValue
{
    public function __construct(
        public int $value,
    ) {}
}

class StringValue implements PrimitiveValue
{
    public function __construct(
        public string $value,
    ) {}
}

class FloatValue implements PrimitiveValue
{
    public function __construct(
        public float $value,
    ) {}
}

class BooleanValue implements PrimitiveValue
{
    public function __construct(
        public bool $value,
    ) {}
}

class SequenceValue implements Value
{
    public function __construct(
        /** Value[] */
        public array $values,
        public ?string $type = null,
    ) {}
}

class DictionaryValue implements Value
{
    public function __construct(
        public ?string $type = null,
        public array $values = [],
    ) {}
}

class StructValue implements Value
{
    public function __construct(
        public string $type,
        /** array<string, mixed> */
        public array $values = [],
    ) {}
}

class EnumValue implements Value
{
    public function __construct(
        public string $type,
        public string|int $value,
    ) {}
}

class DateTimeValue implements Value
{
    public function __construct(
        public string $dateTime,
        public string $dateTimeZone,
        public bool $immutable = true,
    ) {}
}
