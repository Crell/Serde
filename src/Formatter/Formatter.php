<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Field;
use Crell\Serde\Dict;
use Crell\Serde\Sequence;

interface Formatter
{
    public function format(): string;

    public function serializeInitialize(): mixed;

    public function serializeFinalize(mixed $runningValue): mixed;

    public function serializeInt(mixed $runningValue, Field $field, int $next): mixed;

    public function serializeFloat(mixed $runningValue, Field $field, float $next): mixed;

    public function serializeString(mixed $runningValue, Field $field, string $next): mixed;

    public function serializeBool(mixed $runningValue, Field $field, bool $next): mixed;

    public function serializeSequence(mixed $runningValue, Field $field, Sequence $next, callable $recursor): mixed;

    public function serializeDictionary(mixed $runningValue, Field $field, Dict $next, callable $recursor): mixed;
}
