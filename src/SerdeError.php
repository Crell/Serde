<?php

declare(strict_types=1);

namespace Crell\Serde;

enum SerdeError
{
    case Missing;
    case NoDefaultValue;
}
