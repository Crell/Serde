<?php

declare(strict_types=1);

namespace Crell\Serde;

interface TypeMapper
{
    public function keyField(): string;

    public function findClass(string $id): ?string;

    public function findIdentifier(string $class): ?string;
}
