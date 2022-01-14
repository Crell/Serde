<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\HasSubAttributes;
use Crell\AttributeUtils\Inheritable;

#[Attribute(Attribute::TARGET_METHOD)]
class MethodSettings implements Inheritable, HasSubAttributes
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
