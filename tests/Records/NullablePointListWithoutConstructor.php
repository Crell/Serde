<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;

readonly final class NullablePointListWithoutConstructor
{
    /**
     * When the object does not have a constructor, We need to specify the default value.
     *
     * In cases of readonly properties, we can't use the default value
     * as the property can't have a default value in that scenario.
     *
     * Therefore, #[Field(default: null)] must be used in such circumstances
     * to avoid the object instantiation with uninitialized properties.
     */
    #[Field(default: null)]
    #[SequenceField(arrayType: Point::class)]
    public array|null $points;
}
