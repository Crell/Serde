<?php

declare(strict_types=1);

namespace Crell\Serde;

use Attribute;

// @todo Should this maybe be called PostDeserialize?
#[Attribute(Attribute::TARGET_METHOD)]
class PostLoad
{

}