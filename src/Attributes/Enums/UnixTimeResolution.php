<?php

namespace Crell\Serde\Attributes\Enums;

enum UnixTimeResolution: int {
    case Seconds = 1;
    case Milliseconds = 1_000;
    case Microseconds = 1_000_000;
}
