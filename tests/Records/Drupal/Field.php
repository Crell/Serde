<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

use Crell\Serde;

#[Serde\Attributes\ClassDef]
class Field
{
    public int $nid;
    public int $delta;
}
