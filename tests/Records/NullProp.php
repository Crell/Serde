<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class NullProp
{
    public function __construct(public ?array $examples) {}
}
