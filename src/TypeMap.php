<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Inheritable;
use Crell\AttributeUtils\TransitiveProperty;

interface TypeMap extends Inheritable, TransitiveProperty
{
    public function keyField(): string;

    public function findClass(string $id): ?string;

    public function findIdentifier(string $class): ?string;
}
