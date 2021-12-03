<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Deserializer;
use Crell\Serde\Field;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeMap;

interface Deformatter
{
    public function format(): string;

    public function initialField(Deserializer $deserializer, string $targetType): Field;

    public function deserializeInitialize(mixed $serialized): mixed;

    public function deserializeInt(mixed $decoded, Field $field): int|SerdeError;

    public function deserializeFloat(mixed $decoded, Field $field): float|SerdeError;

    public function deserializeBool(mixed $decoded, Field $field): bool|SerdeError;

    public function deserializeString(mixed $decoded, Field $field): string|SerdeError;

    public function deserializeSequence(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError;

    public function deserializeDictionary(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError;

    public function deserializeObject( mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError;

    public function deserializeFinalize(mixed $decoded): void;
}
