<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

use Crell\Serde;

#[Serde\ClassDef]
class Field
{
    public int $nid;
    public int $delta;
}
