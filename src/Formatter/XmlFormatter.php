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
use Crell\Serde\SerdeError;
use Crell\Serde\Serializer;
use Crell\Serde\TypeMapper;

class XmlFormatter implements Formatter /*, Deformatter */
{

    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    public function format(): string
    {
        return 'xml';
    }

    public function initialField(Serializer $serializer, string $type): Field
    {
        $shortName = substr(strrchr($type, '\\'), 1);
        return Field::create(serializedName: $shortName, phpType: $type);
    }

    public function serializeInitialize(ClassDef $classDef): mixed
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
     * @param Field $field
     * @param int $next
     * @return mixed
     */
    public function serializeInt(mixed $runningValue, Field $field, int $next): \DOMNode
    {
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $runningValue;
    }

    public function serializeFloat(mixed $runningValue, Field $field, float $next): mixed
    {
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $runningValue;
    }

    public function serializeString(mixed $runningValue, Field $field, string $next): mixed
    {
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $runningValue;
    }

    public function serializeBool(mixed $runningValue, Field $field, bool $next): mixed
    {
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $runningValue;
    }

    public function serializeSequence(mixed $runningValue, Field $field, Sequence $next, Serializer $serializer): mixed
    {
        // TODO: Implement serializeSequence() method.
    }

    /**
     * @param \DOMNode $runningValue
     * @param Field $field
     * @param Dict $next
     * @param Serializer $serializer
     * @return \DOMNode
     */
    public function serializeDictionary(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): \DOMNode
    {
        $doc = $runningValue->ownerDocument ?? $runningValue;
        $node = $doc->createElement($field->serializedName);

        // PHPStorm will complain about the argument names. PHPStorm is wrong.
        // Its stubs are woefully out of date.
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
