<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Serializer;

trait StreamFormatter
{

    public function rootField(Serializer $serializer, string $type): Field
    {
        return Field::create('root', $type);
    }

    public function serializeInitialize(ClassSettings $classDef, Field $rootField): FormatterStream
    {
        return FormatterStream::new(fopen('php://temp/', 'wb'));
    }

    public function serializeFinalize(mixed $runningValue, ClassSettings $classDef): mixed
    {
        return $runningValue;
    }

    public function serializeInt(mixed $runningValue, Field $field, ?int $next): mixed
    {
        $runningValue->write((string)$next);
        return $runningValue;
    }

    public function serializeFloat(mixed $runningValue, Field $field, ?float $next): mixed
    {
        $runningValue->write((string)$next);
        return $runningValue;
    }

    public function serializeString(mixed $runningValue, Field $field, ?string $next): mixed
    {
        $runningValue->printf('"%s"', $next);
        return $runningValue;
    }

    public function serializeBool(mixed $runningValue, Field $field, ?bool $next): mixed
    {
        $runningValue->write($next ? 'true' : 'false');
        return $runningValue;
    }
}
