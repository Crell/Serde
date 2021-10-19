<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

use Crell\Serde;

class FieldItemList
{
    public function __construct(
        public string $langcode = 'en',
        /** @var array<int, Field> */
        #[Serde\Field(arrayType: Field::class)]
        public array $list = [],
    ) {
    }
}
