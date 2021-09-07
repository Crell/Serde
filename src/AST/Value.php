<?php

declare(strict_types=1);

namespace Crell\Serde\AST;

interface Value
{

}

class IntegerValue implements Value
{
    public function __construct(
        public int $value,
    ) {}
}

class StringValue implements Value
{
    public function __construct(
        public string $value,
    ) {}
}

class FloatValue implements Value
{
    public function __construct(
        public float $value,
    ) {}
}

class BooleanValue implements Value
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
