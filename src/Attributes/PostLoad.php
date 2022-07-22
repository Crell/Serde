<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

use Attribute;
use Crell\AttributeUtils\SupportsScopes;

#[Attribute(Attribute::TARGET_METHOD)]
class PostLoad implements SupportsScopes
{
    /**
     * @param array<string|null> $scopes
     */
    public function __construct(protected array $scopes = [null]) {}

    /**
     *
     *
     * @return array<string|null>
     */
    public function scopes(): array
    {
        return $this->scopes;
    }
}
