<?php

declare(strict_types=1);

namespace Crell\Serde\PropertyHandler;

use Crell\Serde\Field;
use Crell\Serde\JsonFormatter;
use Crell\Serde\Records\Tasks\BigTask;
use Crell\Serde\Records\Tasks\SmallTask;
use Crell\Serde\Records\Tasks\Task;
use function Crell\fp\any;

class MappedObjectPropertyReader extends ObjectPropertyReader
{

    public function readValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $value, mixed $runningValue): mixed
    {
        return $formatter->serializeObject($runningValue, $field, $value, $recursor, [$this->keyName() => $this->classToIdentifier($value::class, $field)]);
    }

    public function canRead(Field $field, mixed $value, string $format): bool
    {
        return is_object($value)
            && any(static fn (string $class) => $value instanceof $class)($this->supportedTypes());
    }

    public function writeValue(JsonFormatter $formatter, callable $recursor, Field $field, mixed $source): mixed
    {
        $phpType = $this->identifierToClass($source[$field->serializedName()][$this->keyName()], $field);
        return parent::writeValue($formatter, $recursor, $field->with(phpType: $phpType), $source);
    }

    public function canWrite(Field $field, string $format): bool
    {
        return any(static fn (string $class) => $field->phpType === $class || is_subclass_of($field->phpType, $class))($this->supportedTypes());
    }

    protected function supportedTypes(): array
    {
        return [Task::class];
    }

    protected function keyName(): string
    {
        return 'size';
    }

    protected function identifierToClass(string $identifier, Field $field): string
    {
        return match ($identifier) {
            'big' => BigTask::class,
            'small' => SmallTask::class,
        };
    }

    protected function classToIdentifier(string $class, Field $field): string
    {
        return match ($class) {
            BigTask::class => 'big',
            SmallTask::class => 'small',
        };
    }
}
