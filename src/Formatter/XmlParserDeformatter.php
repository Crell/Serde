<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Deserializer;
use Crell\Serde\DictionaryField;
use Crell\Serde\Field;
use Crell\Serde\GenericXmlParser;
use Crell\Serde\SequenceField;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeCategory;
use Crell\Serde\XmlElement;
use Crell\Serde\XmlFormat;
use function Crell\fp\firstValue;
use function Crell\fp\keyedMap;
use function Crell\fp\pipe;
use function Crell\fp\reduceWithKeys;

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

    /**
     * @param string $serialized
     */
    public function deserializeInitialize(mixed $serialized): XmlElement
    {
        return $this->parser->parseXml($serialized);
    }

    /**
     * @param XmlElement $decoded
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

    /**
     * @param XmlElement $decoded
     */
    public function deserializeFloat(mixed $decoded, Field $field): float|SerdeError
    {
        $value = $this->getValueFromElement($decoded, $field);

        // @todo Still not sure what to do with this.
        if (!is_numeric($value)) {
            return SerdeError::FormatError;
        }

        return (float)$value;
    }

    /**
     * @param XmlElement $decoded
     */
    public function deserializeBool(mixed $decoded, Field $field): bool|SerdeError
    {
        $value = $this->getValueFromElement($decoded, $field);

        // @todo Still not sure what to do with this.
        if (!is_numeric($value)) {
            return SerdeError::FormatError;
        }

        return (bool)$value;
    }

    /**
     * @param XmlElement $decoded
     */
    public function deserializeString(mixed $decoded, Field $field): string|SerdeError
    {
        return $this->getValueFromElement($decoded, $field);
    }

    /**
     * @param XmlElement[] $decoded
     */
    public function deserializeSequence(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError
    {
        if (empty($decoded)) {
            return SerdeError::Missing;
        }

        $class = $field?->typeField?->arrayType ?? null;

        $upcast = function(array $ret, XmlElement $v, int|string $k) use ($deserializer, $class) {
            $map = $class ? $deserializer->typeMapper->typeMapForClass($class) : null;
            // @todo This will need to get more robust once we support attribute-based values.
            $arrayType = $map?->findClass($v[$map?->keyField()]) ?? $class ?? get_debug_type($v->content);
            $f = Field::create(serializedName: $v->name, phpType: $arrayType);
            $ret[$k] = $deserializer->deserialize($v, $f);
            return $ret;
        };

        return reduceWithKeys([], $upcast)($decoded);
    }

    /**
     * @param XmlElement[] $decoded
     */
    public function deserializeDictionary(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError
    {
        if (empty($decoded)) {
            return SerdeError::Missing;
        }

        $class = $field?->typeField?->arrayType ?? null;

        $data = $this->groupedChildren($decoded[0]);

        $map = $class ? $deserializer->typeMapper->typeMapForClass($class) : null;
        // @todo This will need to get more robust once we support attribute-based values.
        // @todo Skipping the type map for the moment.
//        $arrayType = $map?->findClass($v[$map?->keyField()]) ?? $type ?? get_debug_type($v->content);

        // This monstrosity is begging to be refactored, but for now at least it works.
        $ret = [];
        foreach ($data as $name => $elements) {
            if (count($elements) > 1) {
                // Must be a nested sequence.
                $f = Field::create(serializedName: $name, phpType: 'array');
                $value = $deserializer->deserialize($elements, $f);
            } else {
                $e = $elements[0];
                if (count($e->children)) {
                    if ($class) {
                        // This is a dictionary of objects.
                        $f = Field::create(serializedName: $e->name, phpType: $class);
                        $value = $deserializer->deserialize($e, $f);
                    } else {
                        // This is probably a nested dictionary?
                        $f = Field::create(serializedName: $e->name, phpType: 'array')
                            ->with(typeField: new DictionaryField());
                        $value = $deserializer->deserialize([$e], $f);
                    }
                } else {
                    // A nested primitive, probably.
                    $elementType = $class ?? get_debug_type($e->content);
                    $f = Field::create(serializedName: $e->name, phpType: $elementType);
                    $value = $deserializer->deserialize($e, $f);
                }
            }
            $ret[$name] = $value;
        }
        return $ret;

        /*
        $arrayType = $class ?? get_debug_type($data[0]->content);
        return pipe($data,
            keyedMap(
                values: static fn ($k, XmlElement $e) => $deserializer->deserialize($e, Field::create(serializedName: "$e->name", phpType: $arrayType)),
                keys: static fn ($k, XmlElement $e) => $e->name,
            )
        );
        */
    }

    /**
     * @param XmlElement $decoded
     */
    public function deserializeObject(mixed $decoded, Field $field, Deserializer $deserializer): array|SerdeError
   {
        if ($decoded?->name !== $field->serializedName) {
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
            } elseif ($propField->typeCategory === TypeCategory::Array) {
                $valueElements = $data[$propField->serializedName] ?? [];
                if ($propField?->typeField instanceof SequenceField || $this->isSequence($valueElements)) {
                    $ret[$propField->serializedName] = $deserializer->deserialize($valueElements, $propField);
                } else {
                    if (!$propField->typeField) {
                        $propField = $propField->with(typeField: new DictionaryField());
                    }
                    $ret[$propField->serializedName] = $deserializer->deserialize($valueElements, $propField);
                }
            } elseif ($propField->typeCategory === TypeCategory::Object || $propField->typeCategory->isEnum()) {
                $ret[$propField->serializedName] = $deserializer->deserialize($data[$propField->serializedName][0] ?? null, $propField);
            } else {
                // @todo This needs to be enhanced to deal with attribute-based values, I think?
                // per-type deserialize methods also deal with that, but since the same element
                // may need to get passed multiple times to account for multiple attributes
                // on one element, I think it's necessary here, too.
                $valueElement = $this->getFieldData($propField, $data)[0];
                $ret[$propField->serializedName] = $deserializer->deserialize($valueElement, $propField);
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
     * @todo This method is a hack and is probably still buggy.
     *
     * @param XmlElement[] $valueElements
     */
    protected function isSequence(array $valueElements): bool
    {
        if (count($valueElements) > 1) {
            return true;
        }
        $element = $valueElements[0];
        if (count($element->children)) {
            return false;
        }
        if ($element->content) {
            return true;
        }
        return false;
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
