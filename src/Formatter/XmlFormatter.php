<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\CollectionItem;
use Crell\Serde\Dict;
use Crell\Serde\Field;
use Crell\Serde\Sequence;
use Crell\Serde\Serializer;

class XmlFormatter implements Formatter /*, Deformatter */
{

    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    public function format(): string
    {
        return 'xml';
    }

    public function rootField(Serializer $serializer, string $type): Field
    {
        $shortName = substr(strrchr($type, '\\'), 1);
        return Field::create(serializedName: $shortName, phpType: $type);
    }

    public function serializeInitialize(ClassDef $classDef, Field $rootField): \DOMDocument
    {
        return new \DOMDocument();
    }

    /**
     * @param \DOMDocument $runningValue
     * @return mixed
     */
    public function serializeFinalize(mixed $runningValue, ClassDef $classDef): string
    {
        return $runningValue->saveXML();
    }

    /**
     * @param \DOMNode $runningValue
     */
    public function serializeInt(mixed $runningValue, Field $field, int $next): \DOMNode
    {
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $runningValue;
    }

    /**
     * @param \DOMNode $runningValue
     */
    public function serializeFloat(mixed $runningValue, Field $field, float $next): mixed
    {
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $runningValue;
    }

    /**
     * @param \DOMNode $runningValue
     */
    public function serializeString(mixed $runningValue, Field $field, string $next): mixed
    {
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $runningValue;
    }

    /**
     * @param \DOMNode $runningValue
     */
    public function serializeBool(mixed $runningValue, Field $field, bool $next): mixed
    {
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $runningValue;
    }

    /**
     * @param \DOMNode $runningValue
     */
    public function serializeSequence(mixed $runningValue, Field $field, Sequence $next, Serializer $serializer): mixed
    {
        return array_reduce(
            array: $next->items,
            callback: static fn(\DomNode $runningValue, CollectionItem $item): \DOMNode
                => $serializer->serialize($item->value, $runningValue, $item->field->with(serializedName: $field->serializedName)),
            initial: $runningValue,
        );
    }

    /**
     * @param \DOMNode $runningValue
     */
    public function serializeDictionary(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): \DOMNode
    {
        $doc = $runningValue->ownerDocument ?? $runningValue;
        $node = $doc->createElement($field->serializedName);

        $node = array_reduce(
            array: $next->items,
            callback: static fn(\DomNode $node, CollectionItem $item) => $serializer->serialize($item->value, $node, $item->field),
            initial: $node,
        );

        /*
        foreach ($field->extraProperties as $k => $v) {
            $dat = $doc->createElement($k, $v);
            $node->appendChild($dat);
        }
        */

        $runningValue->appendChild($node);
        return $runningValue;
    }

    public function serializeObject(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): mixed
    {
        return $this->serializeDictionary($runningValue, $field, $next, $serializer);
    }
}
