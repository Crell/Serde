<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

class TextItem extends Field
{
    public function __construct(
        public string $value,
        public string $format,
    ) {
    }

    protected string $processed;

    public function processed(): string
    {
        return $this->processed ??= $this->formatValue();
    }

    protected function formatValue(): string
    {
        // Something fancier goes here, obviously.
        return $this->value;
    }
}
