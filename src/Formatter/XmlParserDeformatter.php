<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\AttributeUtils\MemoryCacheAnalyzer;
use Crell\Serde\ClassDef;
use Crell\Serde\Field;
use Crell\Serde\Serde;
use Crell\Serde\SerdeError;
use Crell\Serde\TypeMapper;

class XmlParserDeformatter implements Deformatter, SupportsCollecting
{
    public function __construct(
        protected readonly ClassAnalyzer $analyzer = new MemoryCacheAnalyzer(new Analyzer()),
    ) {}

    public function format(): string
    {
        return 'xml';
    }

    public function initialField(string $targetType): Field
    {
        $shortName = substr(strrchr($targetType, '\\'), 1);
        return Field::create(serializedName: $shortName, phpType: $targetType);
    }

    public function deserializeInitialize(mixed $serialized): mixed
    {
        return $this->parseTags($serialized);
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

    /**
     *
     *
     * @param array $decoded
     * @param Field $field
     * @param callable $recursor
     * @param TypeMapper|null $typeMap
     * @return array|SerdeError
     */
    public function deserializeObject(mixed $decoded, Field $field, callable $recursor, ?TypeMapper $typeMap): array|SerdeError
    {
        if ($decoded[0]['tag'] !== $field->serializedName) {
            return SerdeError::Missing;
        }

        // @todo Should be an exception, maybe?
        if ($decoded[0]['type'] !== 'open') {
            return SerdeError::FormatError;
        }

        $data = $this->extractSubEntries($decoded);

        $properties = $this->propertyList($field, $typeMap, $data);

        $collectingField = null;
        $usedNames = [];

        $ret = [];

        // First pull out the properties we know about.
        /** @var Field $prop */
        foreach ($properties as $prop) {
            $usedNames[] = $prop->serializedName;
            if ($prop->flatten) {
                $collectingField = $prop;
                continue;
            }
            $ret[$prop->serializedName] = ($prop->typeCategory->isEnum() || $prop->typeCategory->isCompound())
                ? $recursor($data, $prop)
                : $this->castScalarType($prop->phpType, $data[$prop->serializedName]['value'] ?? SerdeError::Missing);
        }

        // Any other values are for a collecting field, if any,
        // but may need further processing according to the collecting field.
        $remaining = $this->getRemainingData($data, $usedNames);
        // Object collecting doesn't support type maps, so can be handled by
        // the generic version in the else clause.
        if ($collectingField?->phpType === 'array' && $collectingField?->typeMap) {
            foreach ($remaining as $name => $entry) {
                $class = $collectingField->typeMap->findClass($entry[$collectingField->typeMap->keyField()]);
                $ret[$name] = $recursor($remaining, Field::create(serializedName: "$name", phpType: $class));
            }
        } else {
            foreach ($remaining as $k => $v) {
                $ret[$k] = $v;
            }
        }

        return $ret;
    }

    protected function extractSubEntries(array $decoded): array
    {
        $data = [];
        foreach ($decoded as $key => $entry) {
            if ($key === 0) {
                continue;
            }
            if ($entry['type'] === 'close') {
                break;
            }
            $data[$entry['tag']] = $entry;
        }
        return $data;
    }

    protected function castScalarType(string $type, mixed $value): int|string|bool|float|SerdeError
    {
        return match ($type) {
            'int' => (int)$value,
            'float' => (float)$value,
            'bool' => (bool)$value,
            'string' => (string)$value,
            SerdeError::Missing => SerdeError::Missing,
        };
    }

    public function deserializeFinalize(mixed $decoded): void
    {
        // TODO: Implement deserializeFinalize() method.
    }

    public function getRemainingData(mixed $source, array $used): mixed
    {
        return array_diff_key($source, array_flip($used));
    }

    /**
     * Parses an XML string into a nested array of tag definitions.
     *
     * @see https://www.php.net/manual/en/function.xml-parse-into-struct.php
     *
     * @param string $xml
     *   A well-formed XML string to parse.
     * @return array
     *   A nested array of tag definitions.  The format is the same as created by
     *   xml_parse_into_struct(), but with defaults added and a few derived properties.
     */
    protected function parseTags(string $xml): array
    {
        $tags = [];
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $xml, $tags);
        xml_parser_free($parser);

        // Ensure all properties are always defined so that we don't have to constantly check for missing values later.
        array_walk($tags, static function (&$tag) {
            [$tagNs, $tagName] = match (\str_contains($tag['tag'], ':')) {
                true => explode(':', $tag['tag']),
                false => [$tag['tag'], ''],
            };
            $tag += [
                'attributes' => [],
                'value' => '',
                'name' => $tagName,
                'namespace' => $tagNs,
            ];
        });

        return $tags;
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
