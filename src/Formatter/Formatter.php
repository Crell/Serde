<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Field;

interface Formatter
{
    public function format(): string;

    public function serializeInitialize(): mixed;

    public function serializeFinalize(mixed $runningValue): string;

    public function serializeInt(mixed $runningValue, Field $field, int $next): mixed;

    public function serializeFloat(mixed $runningValue, Field $field, float $next): mixed;

    public function serializeString(mixed $runningValue, Field $field, string $next): mixed;

    public function serializeBool(mixed $runningValue, Field $field, bool $next): mixed;

    public function serializeArray(mixed $runningValue, Field $field, array $next, callable $recursor): mixed;

    public function serializeDictionary(mixed $runningValue, Field $field, array $next, callable $recursor): mixed;
}
