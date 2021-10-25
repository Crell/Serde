<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\Dict;
use Crell\Serde\Field;
use Crell\Serde\Sequence;
use Crell\Serde\SerdeError;
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
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $node;
    }

    public function serializeFloat(mixed $runningValue, Field $field, float $next): mixed
    {
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $node;
    }

    public function serializeString(mixed $runningValue, Field $field, string $next): mixed
    {
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $node;
    }

    public function serializeBool(mixed $runningValue, Field $field, bool $next): mixed
    {
        $node = $runningValue->ownerDocument->createElement($field->serializedName, (string)$next);
        $runningValue->appendChild($node);
        return $node;
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
        $doc = $runningValue->ownerDocument ?? $runningValue;
        $node = $doc->createElement($field->serializedName);
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
        return new \DOMDocument($serialized);
    }

    /**
     *
     *
     * @param \DOMNode $decoded
     * @param Field $field
     * @return int|SerdeError
     */
    public function deserializeInt(mixed $decoded, Field $field): int|SerdeError
    {
        return (int)$decoded->textContent;
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

    /**
     * @param \DOMNode $decoded
     * @param Field $field
     * @param callable $recursor
     * @param TypeMapper|null $typeMap
     * @return array|SerdeError
     */
    public function deserializeObject(mixed $decoded, Field $field, callable $recursor, ?TypeMapper $typeMap): array|SerdeError
    {
        $data = [];

        var_dump($decoded);

        /** @var \DOMNode $node */
        foreach ($decoded->childNodes as $node) {
            $data[$node->nodeName] = $node;
        }

        $collectingField = null;
        $usedNames = [];

        $properties = $this->propertyList($field, $typeMap, (array)$data);

        // First pull out the properties we know about.
        /** @var Field $prop */
        foreach ($properties as $prop) {
            $usedNames = $prop->serializedName;
            if ($prop->flatten) {
                $collectingField = $prop;
                continue;
            }
            $ret[$prop->serializedName] = $recursor($decoded, $prop);

//            ($prop->typeCategory->isEnum() || $prop->typeCategory->isCompound())
//                ? $recursor($decoded, $prop)
//                : $data[$prop->serializedName] ?? SerdeError::Missing;
        }

        return $ret;
    }

    public function deserializeFinalize(mixed $decoded): void
    {
        // TODO: Implement deserializeFinalize() method.
    }


    /**
     * Gets the property list for a given object.
     *
     * We need to know the object properties to deserialize to.
     * However, that list may be modified by the type map, as
     * the type map is in the incoming data.
     * The key field is kept in the data so that the property writer
     * can also look up the right type.
     */
    protected function propertyList(Field $field, ?TypeMapper $map, array $data): array
    {
        $class = $map
            ? $map->findClass($data[$map->keyField()])
            : $field->phpType;

        return $this->getAnalyzer()->analyze($class, ClassDef::class)->properties;
    }

    protected function getAnalyzer(): ClassAnalyzer
    {
        return $this->analyzer;
    }
}
