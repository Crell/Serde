<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

class StringItem extends Field
{
    public function __construct(public string $value)
    {
    }
}
