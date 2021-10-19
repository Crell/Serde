<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

class LinkItem extends Field
{
    public function __construct(
        public string $uri,
        public string $title,
        public array $options = [],
    ) {
    }
}
