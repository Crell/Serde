<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Tasks;

class TaskContainer
{
    public function __construct(
        public Task $task,
    ) {}
}
