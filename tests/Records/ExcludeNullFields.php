<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\Field;

class ExcludeNullFields
{
    public function __construct(
        public string $name,
        #[Field(omitIfNull: true)]
        public ?int $age = null,
    ) {}
}
