<?php

namespace Crell\Serde\Records\ValueObjects;

class JobEntry
{
    public function __construct(
        public JobDescription $description,
    ) {}
}
