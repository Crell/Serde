<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class NonPromotedDefault
{
    public string $value;

    public function __construct(
        string|array $value = []
    ) {
        $this->value = is_array($value) ? implode(',', $value) : $value;
    }
}
