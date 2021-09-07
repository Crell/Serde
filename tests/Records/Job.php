<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

/**
 * A simple object, that is a foreign relation from Employee.
 */
class Job
{
    public int $id;

    public function __construct(
        public string $title,
        public string $description,
        public int $pay,
    ) {}
}
