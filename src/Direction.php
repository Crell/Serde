<?php

declare(strict_types=1);

namespace Crell\Serde;

enum Direction
{
    case Serialize;
    case Deserialize;
}
