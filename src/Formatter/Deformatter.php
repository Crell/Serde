<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;
use Crell\Serde\SerdeError;

interface Deformatter
{
    public function format(): string;

    public function rootField(Deserializer $deserializer, string $targetType): Field;

    public function deserializeInitialize(mixed $serialized, Field $rootField): mixed;

    public function deserializeInt(mixed $decoded, Field $field): int|SerdeError;

    public function deserializeFloat(mixed $decoded, Field $field): float|SerdeError;

    public function deserializeBool(mixed $decoded, Field $field): bool|SerdeError;

    public function deserializeString(mixed $decoded, Field $field): string|SerdeError;

    /**
     * @param mixed $decoded
     * @param Field $field
     * @param Deserializer $deserializer
     * @return mixed[]|SerdeError
     */
    public function deserializeSequence(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError;

    /**
     * @param mixed $decoded
     * @param Field $field
     * @param Deserializer $deserializer
     * @return array<int|string, mixed>|SerdeError
     */
    public function deserializeDictionary(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError;

    /**
     * @param mixed $decoded
     * @param Field $field
     * @param Deserializer $deserializer
     * @return array<string, mixed>|SerdeError
     */
    public function deserializeObject( mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError;

    public function deserializeFinalize(mixed $decoded): void;
}
