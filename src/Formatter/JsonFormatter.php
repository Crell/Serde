<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Deserializer;
use JsonException;

class JsonFormatter implements Formatter, Deformatter, SupportsCollecting, SupportsTypeIntrospection
{
    use ArrayBasedFormatter;
    use ArrayBasedDeformatter;

    public function format(): string
    {
        return 'json';
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeInitialize(ClassSettings $classDef, Field $rootField): array
    {
        return ['root' => []];
    }

    /**
     * @throws JsonException
     */
    public function serializeFinalize(mixed $runningValue, ClassSettings $classDef): string
    {
        return json_encode($runningValue['root'], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string|int, mixed>
     * @throws JsonException
     */
    public function deserializeInitialize(
        mixed $serialized,
        ClassSettings $classDef,
        Field $rootField,
        Deserializer $deserializer
    ): array
    {
        return ['root' => json_decode($serialized ?: '{}', true, 512, JSON_THROW_ON_ERROR)];
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }
}
