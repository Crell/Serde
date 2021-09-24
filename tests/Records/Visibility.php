<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

class Visibility
{
    public function __construct(
        public int $public,
        protected int $protected,
        private int $private,
        private ?Visibility $visibility = null,
    ) {}
}
