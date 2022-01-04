<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\ClassDef;
use Crell\Serde\Field;

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
    public function serializeInitialize(ClassDef $classDef, Field $rootField): array
    {
        return ['root' => []];
    }

    public function serializeFinalize(mixed $runningValue, ClassDef $classDef): string
    {
        return json_encode($runningValue['root'], JSON_THROW_ON_ERROR);
    }

    public function deserializeInitialize(mixed $serialized, Field $rootField): mixed
    {
        return ['root' => json_decode($serialized, true, 512, JSON_THROW_ON_ERROR)];
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }
}
