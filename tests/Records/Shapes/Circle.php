<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Shapes;

class Circle implements Shape
{
    public function __construct(
        public TwoDPoint $center,
        public int $radius,
    ) {}

    public function area(): float
    {
        return M_PI * $this->radius * $this->radius;
    }
}
