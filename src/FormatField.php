<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;
use Crell\AttributeUtils\Inheritable;

#[Attribute(Attribute::IS_REPEATABLE)]
interface FormatField extends Inheritable
{

}
