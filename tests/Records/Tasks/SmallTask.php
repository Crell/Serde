<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Tasks;

class SmallTask implements Task
{
    public function __construct(public string $name) {}
}
