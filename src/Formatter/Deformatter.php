<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use Crell\Serde\DeformatterResult;
use Crell\Serde\Deserializer;

/**
 * Decode data from a given format when called by an Importer.
 *
 * It is this class's responsibility to enforce "strict mode" on each field
 * type.  That may vary slightly depending on the format. (eg, in XML, everything
 * is a string by default so just checking the variable type is not viable.) In
 * strict mode, invalid values should throw a TypeMismatch.  In weak mode, a
 * good faith effort should be made to convert the data to the expected type.
 */
interface Deformatter
{
    public function format(): string;

    public function rootField(Deserializer $deserializer, string $targetType): Field;

    public function deserializeInitialize(
        mixed $serialized,
        ClassSettings $classDef,
        Field $rootField,
        Deserializer $deserializer
    ): mixed;

    public function deserializeInt(mixed $decoded, Field $field): int|DeformatterResult|null;

    public function deserializeFloat(mixed $decoded, Field $field): float|DeformatterResult|null;

    public function deserializeBool(mixed $decoded, Field $field): bool|DeformatterResult|null;

    public function deserializeString(mixed $decoded, Field $field): string|DeformatterResult|null;

    public function deserializeNull(mixed $decoded, Field $field): DeformatterResult|null;

    /**
     * @param mixed $decoded
     * @param Field $field
     * @param Deserializer $deserializer
     * @return array<int|string, mixed>|DeformatterResult|null
     */
    public function deserializeSequence(mixed $decoded, Field $field, Deserializer $deserializer): array|DeformatterResult|null;

    /**
     * @param mixed $decoded
     * @param Field $field
     * @param Deserializer $deserializer
     * @return array<string|int, mixed>|DeformatterResult|null
     */
    public function deserializeDictionary(mixed $decoded, Field $field, Deserializer $deserializer): array|DeformatterResult|null;

    /**
     * @param mixed $decoded
     * @param Field $field
     * @param Deserializer $deserializer
     * @return array<string, mixed>|DeformatterResult|null
     */
    public function deserializeObject( mixed $decoded, Field $field, Deserializer $deserializer): array|DeformatterResult|null;

    public function deserializeFinalize(mixed $decoded): void;
}
