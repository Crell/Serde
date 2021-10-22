<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Dict;
use Crell\Serde\Field;
use Crell\Serde\Sequence;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeMapper;

class XmlFormatter implements Formatter, Deformatter
{

    public function format(): string
    {
        return 'xml';
    }

    public function initialField(string $type): Field
    {
        $shortName = substr(strrchr($type, '\\'), 1);
        return Field::create(serializedName: $shortName, phpType: $type);
    }

    public function serializeInitialize(): mixed
    {
        return new \DOMDocument();
    }

    /**
     * @param \DOMDocument $runningValue
     * @return mixed
     */
    public function serializeFinalize(mixed $runningValue): string
    {
        return $runningValue->saveXML();
    }

    /**
     *
     *
     * @param \DOMNode $runningValue
     * @param Field $field
     * @param int $next
     * @return mixed
     */
    public function serializeInt(mixed $runningValue, Field $field, int $next): \DOMNode
    {
        $node = $runningValue->createElement($field->serializedName, $next);
        $runningValue->appendChild($node);
        return $runningValue;
    }

    public function serializeFloat(mixed $runningValue, Field $field, float $next): mixed
    {
        // TODO: Implement serializeFloat() method.
    }

    public function serializeString(mixed $runningValue, Field $field, string $next): mixed
    {
        // TODO: Implement serializeString() method.
    }

    public function serializeBool(mixed $runningValue, Field $field, bool $next): mixed
    {
        // TODO: Implement serializeBool() method.
    }

    public function serializeSequence(mixed $runningValue, Field $field, Sequence $next, callable $recursor): mixed
    {
        // TODO: Implement serializeSequence() method.
    }

    /**
     * @param \DOMNode $runningValue
     * @param Field $field
     * @param Dict $next
     * @param callable $recursor
     * @return \DOMNode
     */
    public function serializeDictionary(mixed $runningValue, Field $field, Dict $next, callable $recursor): \DOMNode
    {
        $node = $runningValue->createElement($field->serializedName);
        foreach ($next->items as $item) {
            $dat = $recursor($item->value, $node, $item->field);
            $node->appendChild($dat);
        }
        foreach ($field->extraProperties as $k => $v) {
            $dat = $runningValue->createElement($k, $v);
            $node->appendChild($dat);
        }
        $runningValue->appendChild($node);
        return $runningValue;
    }

    public function deserializeInitialize(mixed $serialized): mixed
    {
        // TODO: Implement deserializeInitialize() method.
    }

    public function deserializeInt(mixed $decoded, Field $field): int|SerdeError
    {
        // TODO: Implement deserializeInt() method.
    }

    public function deserializeFloat(mixed $decoded, Field $field): float|SerdeError
    {
        // TODO: Implement deserializeFloat() method.
    }

    public function deserializeBool(mixed $decoded, Field $field): bool|SerdeError
    {
        // TODO: Implement deserializeBool() method.
    }

    public function deserializeString(mixed $decoded, Field $field): string|SerdeError
    {
        // TODO: Implement deserializeString() method.
    }

    public function deserializeSequence(mixed $decoded, Field $field, callable $recursor): array|SerdeError
    {
        // TODO: Implement deserializeSequence() method.
    }

    public function deserializeDictionary(mixed $decoded, Field $field, callable $recursor): array|SerdeError
    {
        // TODO: Implement deserializeDictionary() method.
    }

    public function deserializeObject(
        mixed $decoded,
        Field $field,
        callable $recursor,
        ?TypeMapper $typeMap
    ): array|SerdeError {
        // TODO: Implement deserializeObject() method.
    }

    public function deserializeFinalize(mixed $decoded): void
    {
        // TODO: Implement deserializeFinalize() method.
    }


}
