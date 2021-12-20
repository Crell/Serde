<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\ClassDef;
use Crell\Serde\CollectionItem;
use Crell\Serde\Dict;
use Crell\Serde\Field;
use Crell\Serde\Sequence;
use Crell\Serde\Serializer;

class JsonStreamFormatter implements Formatter
{
    public function format(): string
    {
        return 'json-stream';
    }

    public function initialField(Serializer $serializer, string $type): Field
    {
        return Field::create('root', $type);
    }

    public function serializeInitialize(ClassDef $classDef): mixed
    {
        return fopen('php://temp/', 'wb');
    }

    public function serializeFinalize(mixed $runningValue, ClassDef $classDef): mixed
    {
        return $runningValue;
    }

    public function serializeInt(mixed $runningValue, Field $field, int $next): mixed
    {
        fwrite($runningValue, sprintf('"%s":%d', $field->serializedName, $next));
        return $runningValue;
    }

    public function serializeFloat(mixed $runningValue, Field $field, float $next): mixed
    {
        fwrite($runningValue, sprintf('"%s":%f', $field->serializedName, $next));
        return $runningValue;
    }

    public function serializeString(mixed $runningValue, Field $field, string $next): mixed
    {
        fwrite($runningValue, sprintf('"%s":"%s"', $field->serializedName, $next));
        return $runningValue;
    }

    public function serializeBool(mixed $runningValue, Field $field, bool $next): mixed
    {
        fwrite($runningValue, sprintf('"%s":%s', $field->serializedName, $next ? 'true' : 'false'));
        return $runningValue;
    }

    public function serializeSequence(mixed $runningValue, Field $field, Sequence $next, Serializer $serializer): mixed
    {
        // TODO: Implement serializeSequence() method.
    }

    public function serializeDictionary(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): mixed
    {
        fwrite($runningValue, '{');

        $isFirst = true;

        /** @var CollectionItem $item */
        foreach ($next->items as $item) {
            if (!$isFirst) {
                fwrite($runningValue, ',');
            }
            $isFirst = false;
            $serializer->serialize($item->value, $runningValue, $item->field);
        }

        foreach ($field->extraProperties as $k => $v) {
            if (!$isFirst) {
                fwrite($runningValue, ',');
            }
            fwrite($runningValue, sprintf('"%s":"%s"\n', $k, $v));
        }

        fwrite($runningValue, '}');

        return $runningValue;
    }

    public function serializeObject(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): mixed
    {
        return $this->serializeDictionary($runningValue, $field, $next, $serializer);
    }

}
