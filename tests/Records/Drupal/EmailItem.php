<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

class EmailItem extends Field
{
    public function __construct(public string $email)
    {
    }
}
