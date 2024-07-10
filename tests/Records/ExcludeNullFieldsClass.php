<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;

#[ClassSettings(omitNullFields: true)]
class ExcludeNullFieldsClass
{
    public function __construct(
        public string $name,
        public ?int $age = null,
    ) {}
}
