<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Inheritable;
use Crell\AttributeUtils\HasSubAttributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class MethodDef implements Inheritable, HasSubAttributes
{
    public readonly bool $postLoadCallback;

    public function subAttributes(): array
    {
        return [
            PostLoad::class => 'fromPostLoad',
        ];
    }

    public function fromPostLoad(?PostLoad $load): void
    {
        $this->postLoadCallback ??= !is_null($load);
    }
}
