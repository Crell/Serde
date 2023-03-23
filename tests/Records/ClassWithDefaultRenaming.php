<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Renaming\Prefix;

#[ClassSettings(renameWith: new Prefix('foo_'))]
class ClassWithDefaultRenaming
{
    public function __construct(
        public string $string = 'A',
        #[Field(serializedName: 'the_number')]
        public int $int = 5,
    ) {}
}
