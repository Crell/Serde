<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;

/**
 * @todo Ideally this should be redone to use some standard attribute-based generics format.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayField extends Field
{

    public function __construct(
        public string $keyType,
        public string $valueType,
        ?string $name = null,
        ?string $default = null,
    ) {
        parent::__construct($name, $default);
    }

}
