<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Shapes;

class Rectangle implements Shape
{
    public function __construct(
        public TwoDPoint $topLeft,
        public TwoDPoint $bottomRight,
    ) {}

    public function area(): float
    {
        return ($this->bottomRight->x - $this->topLeft->x) * ($this->bottomRight->y - $this->topLeft->y);
    }
}
