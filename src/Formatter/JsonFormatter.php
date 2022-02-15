<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;

class JsonFormatter implements Formatter, Deformatter, SupportsCollecting
{
    use ArrayBasedFormatter;
    use ArrayBasedDeformatter;

    public function format(): string
    {
        return 'json';
    }

    /**
     * @param Field $rootField
     * @return array<string, mixed>
     */
    public function serializeInitialize(ClassSettings $classDef, Field $rootField): array
    {
        return ['root' => []];
    }

    public function serializeFinalize(mixed $runningValue, ClassSettings $classDef): string
    {
        return json_encode($runningValue['root'], JSON_THROW_ON_ERROR);
    }

    public function deserializeInitialize(mixed $serialized, Field $rootField): mixed
    {
        return ['root' => json_decode($serialized ?: '{}', true, 512, JSON_THROW_ON_ERROR)];
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }
}
