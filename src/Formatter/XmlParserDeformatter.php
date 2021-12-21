<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\Deserializer;
use Crell\Serde\Field;
use Crell\Serde\GenericXmlParser;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeCategory;
use Crell\Serde\TypeMapper;
use Crell\Serde\XmlElement;
use Crell\Serde\XmlFormat;
use function Crell\fp\firstValue;
use function Crell\fp\pipe;

class XmlParserDeformatter implements Deformatter, SupportsCollecting
{
    public function __construct(
        private GenericXmlParser $parser = new GenericXmlParser(),
    ) {}

    public function format(): string
    {
        return 'xml';
    }

    public function initialField(Deserializer $deserializer, string $targetType): Field
    {
        $shortName = substr(strrchr($targetType, '\\'), 1);
        return Field::create(serializedName: $shortName, phpType: $targetType);
    }

    public function deserializeInitialize(mixed $serialized): XmlElement
    {
        return $this->parser->parseXml($serialized);
    }

    /**
     * @param XmlElement $decoded
     * @param Field $field
     * @return int|SerdeError
     */
    public function deserializeInt(mixed $decoded, Field $field): int|SerdeError
    {
        $value = $this->getValueFromElement($decoded, $field);

        // @todo Still not sure what to do with this.
        if (!is_numeric($value)) {
            return SerdeError::FormatError;
        }

        return (int)$value;
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

    public function deserializeSequence(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError
    {
        // TODO: Implement deserializeSequence() method.
    }

    public function deserializeDictionary(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError
    {
        // TODO: Implement deserializeDictionary() method.
    }

    /**
     *
     *
     * @param XmlElement $decoded
     * @param Field $field
     * @param Deserializer $deserializer
     * @param TypeMapper|null $typeMap
     * @return array|SerdeError
     */
    public function deserializeObject(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError
   {
        if ($decoded->name !== $field->serializedName) {
            return SerdeError::Missing;
        }

        $data = $this->groupedChildren($decoded);
        // @todo This is going to break on typemapped fields, but deal with that later.
        $properties = $deserializer->typeMapper->propertyList($field, $data);

        $usedNames = [];
        $collectingArray = null;
        /** @var Field[] $collectingObjects */
        $collectingObjects = [];

        $ret = [];

        // First pull out the properties we know about.
        /** @var Field $propField */
        foreach ($properties as $propField) {
            $usedNames[] = $propField->serializedName;
            if ($propField->flatten && $propField->typeCategory === TypeCategory::Array) {
                $collectingArray = $propField;
            } elseif ($propField->flatten && $propField->typeCategory === TypeCategory::Object) {
                $collectingObjects[] = $propField;
            } else {
                if ($propField->typeCategory->isEnum() || $propField->typeCategory->isCompound()) {
                    $deserializer->deserialize($data, $propField);
                } else {
                    // @todo This needs to be enhanced to deal with attribute-based values, I think?
                    // per-type deserialize methods also deal with that, but since the same element
                    // may need to get passed multiple times to account for multiple attributes
                    // on one element, I think it's necessary here, too.
                    $valueElement = $this->getFieldData($propField, $data)[0];

                    $ret[$propField->serializedName] = $deserializer->deserialize($valueElement, $propField);
                }
            }
        }

        /*
        // Any other values are for a collecting field, if any,
        // but may need further processing according to the collecting field.
        $remaining = $this->getRemainingData($data, $usedNames);
        // Object collecting doesn't support type maps, so can be handled by
        // the generic version in the else clause.
        if ($collectingField?->phpType === 'array' && $collectingField?->typeMap) {
            foreach ($remaining as $name => $entry) {
                $class = $collectingField->typeMap->findClass($entry[$collectingField->typeMap->keyField()]);
                $ret[$name] = $deserializer->deserialize($remaining, Field::create(serializedName: "$name", phpType: $class));
            }
        } else {
            foreach ($remaining as $k => $v) {
                $ret[$k] = $v;
            }
        }
*/
        return $ret;
    }

    /**
     * @param Field $field
     * @param array $data
     * @return XmlElement[]
     */
    public function getFieldData(Field $field, array $data): mixed
    {
        return firstValue(fn(string $name): mixed => $data[$name] ?? null)([$field->serializedName, ...$field->alias]);
    }

    protected function groupedChildren(XmlElement $element): array
    {
        $fn = static function (array $collection, XmlElement $child) {
            $name = $child->name;
            $collection[$name] ??= [];
            $collection[$name][] = $child;
            return $collection;
        };

        return array_reduce($element->children, $fn, []);
    }

    protected function getValueFromElement(XmlElement $element, Field $field): mixed
    {
        $atName = ($field->formats[XmlFormat::class] ?? null)?->attributeName;

        return $atName
            ? ($element->attributes[$atName] ?? SerdeError::Missing)
            : $element->content;
    }

    public function deserializeFinalize(mixed $decoded): void
    {
    }

    public function getRemainingData(mixed $source, array $used): mixed
    {
        return array_diff_key($source, array_flip($used));
    }

}
