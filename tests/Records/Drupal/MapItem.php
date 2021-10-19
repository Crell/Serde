<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

// We can totally do better than this thanks to JSON.
class MapItem extends Field
{
    public string $value;
}
