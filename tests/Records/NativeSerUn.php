<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class NativeSerUn
{
    public function __construct(
        public int $a,
        public string $b,
        public \DateTimeImmutable $c,
    ) {}

    public function __serialize(): array
    {
        return [
            'a2' => $this->a,
            'b2' => $this->b,
            'c2' => $this->c->format(\DateTimeInterface::RFC3339_EXTENDED),
        ];
    }

    public function __unserialize(array $input): void
    {
        $this->a = $input['a2'];
        $this->b = $input['b2'];
        $this->c = new \DateTimeImmutable($input['c2']);
    }
}
