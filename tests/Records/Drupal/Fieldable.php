<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

use Crell\Serde;

trait Fieldable
{
    /** @var array<int, FieldItemList> */
    #[Serde\Attributes\SequenceField(arrayType: FieldItemList::class)]
    public array $fields;
}
