<?php

declare(strict_types=1);

namespace Crell\Serde;

use function Crell\fp\pipe;

class GenericXmlParser
{
    /**
     * Parses an XML string into a nested tree of XmlElement objects.
     *
     * If an empty string is passed, null is returned.
     *
     * @param string $xml
     * @return ?XmlElement
     */
    public function parseXml(string $xml): ?XmlElement
    {
        return pipe($xml,
            $this->parseTags(...),
            $this->normalizeTags(...),
            $this->upcast(...),
        );
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

        return $tags;
    }

    /**
     * Normalizes an xml_parser tag list to ensure default values.
     *
     * @see https://www.php.net/manual/en/function.xml-parse-into-struct.php
     *
     * @param array $tags
     *   An array of tags as returned by xml_parser.
     * @return array
     *   A nested array of tag definitions.  The format is the same as created by
     *   xml_parse_into_struct(), but with defaults added and a few derived properties.
     */
    protected function normalizeTags(array $tags): array
    {
        // Ensure all properties are always defined so that we don't have to constantly check for missing values later.
        array_walk($tags, static function (&$tag) {
            [$tagNs, $tagName] = match (\str_contains($tag['tag'], ':')) {
                true => explode(':', $tag['tag']),
                false => ['', $tag['tag']],
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
     * Upcast a set of tag arrays to a tree of XmlElement objects.
     *
     * Originally inspired on http://php.net/manual/en/function.xml-parse-into-struct.php#66487
     *
     * @param array $tags
     *   The enhanced tag list as returned by normalizeTags().
     * @return ?XmlElement
     */
    protected function upcast(array $tags): ?XmlElement
    {
        $elements = [];  // the currently filling [child] XmlElement array
        $stack = [];
        foreach ($tags as $tag) {
            $index = count($elements);
            if (in_array($tag['type'], ["complete", "open"], true)) {
                $elements[$index] = XmlElement::fromTag($tag);
                if ($tag['type'] === "open") {  // push
                    $stack[count($stack)] = &$elements;
                    $elements = &$elements[$index]->children;
                }
            } elseif ($tag['type'] === "close") {  // pop
                $elements = &$stack[count($stack) - 1];
                unset($stack[count($stack) - 1]);
            }
        }
        return $elements[0] ?? null;  // the single top-level element
    }
}
