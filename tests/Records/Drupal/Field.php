<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

use Crell\Serde;

#[Serde\Attributes\ClassSettings]
class Field
{
    public int $nid;
    public int $delta;
}
