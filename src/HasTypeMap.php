<?php

declare(strict_types=1);

namespace Crell\Serde;

// @todo This should probably not be a trait, and we just suck up the duplication.
trait HasTypeMap
{
    public readonly ?TypeMapper $typeMap;

    public function subAttributes(): array
    {
        return [TypeMapper::class => 'fromTypeMap'];
    }

    public function fromTypeMap(?TypeMapper $map): void
    {
        // This may assign to null, which is OK as that will
        // evaluate to false when we need it to.
        $this->typeMap = $map;
    }
}
