<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use Crell\Serde\DeformatterResult;
use Crell\Serde\Deserializer;
use Devium\Toml\Toml;
use Devium\Toml\TomlError;

class TomlFormatter implements Formatter, Deformatter, SupportsCollecting
{
    use ArrayBasedFormatter;
    use ArrayBasedDeformatter;

    public function format(): string
    {
        return 'toml';
    }

    /**
     * @param ClassSettings $classDef
     * @param Field $rootField
     * @return array<string, mixed>
     */
    public function serializeInitialize(ClassSettings $classDef, Field $rootField): array
    {
        return ['root' => []];
    }

    /**
     * @throws TomlError
     */
    public function serializeFinalize(mixed $runningValue, ClassSettings $classDef): string
    {
        return Toml::encode($runningValue['root']);
    }

    /**
     * @param mixed $serialized
     * @param ClassSettings $classDef
     * @param Field $rootField
     * @param Deserializer $deserializer
     * @return array<string, mixed>
     * @throws TomlError
     */
    public function deserializeInitialize(
        mixed $serialized,
        ClassSettings $classDef,
        Field $rootField,
        Deserializer $deserializer
    ): array
    {
        return ['root' => Toml::decode($serialized ?: '', true)];
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }

    public function deserializeFloat(mixed $decoded, Field $field): float|DeformatterResult|null
    {
        if (!array_key_exists($field->serializedName, $decoded)) {
            return DeformatterResult::Missing;
        }

        return (float)($decoded[$field->serializedName]);
    }
}
