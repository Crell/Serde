<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;

class ArrayFormatter implements Formatter, Deformatter, SupportsCollecting
{
    use ArrayBasedFormatter;
    use ArrayBasedDeformatter;

    public function format(): string
    {
        return 'array';
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeInitialize(ClassSettings $classDef, Field $rootField): array
    {
        return ['root' => []];
    }

    public function serializeFinalize(mixed $runningValue, ClassSettings $classDef): mixed
    {
        return $runningValue['root'];
    }

    /**
     * @param mixed $source
     *   The deformatter-specific source value being passed around.
     * @param string[] $used
     *   A list of property names have have already been extracted, and so are
     *   not "remaining."
     * @return array<string, mixed>
     */
    public function getRemainingData(mixed $source, array $used): array
    {
        return array_diff_key($source, array_flip($used));
    }

    /**
     * @return array<string, mixed>
     */
    public function deserializeInitialize(mixed $serialized, Field $rootField): array
    {
        return ['root' => $serialized ?: []];
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }
}
