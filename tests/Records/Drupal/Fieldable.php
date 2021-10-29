<?php

declare(strict_types=1);

namespace Crell\Serde\Records\Drupal;

use Crell\Serde;

trait Fieldable
{
    /** @var array<int, FieldItemList> */
    #[Serde\SequenceField(arrayType: FieldItemList::class)]
    public array $fields;
}
