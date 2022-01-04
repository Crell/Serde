<?php

declare(strict_types=1);

namespace Crell\Serde;

/**
 * @phpstan-import-type TagDefinition from GenericXmlParser
 */
class XmlElement
{
    public readonly string $name;
    public readonly string $namespace;
    public readonly string $content;

    /**
     * @var array<string, string>
     */
    public readonly array $attributes;

    /**
     * @var array<string, static>
     */
    public array $children = [];

    /**
     *
     *
     * @param TagDefinition $tag
     * @return self
     */
    public static function fromTag(array $tag): self
    {
        $new = new self();
        $new->name = $tag['name'];
        $new->namespace = $tag['namespace'];
        $new->attributes = $tag['attributes'];
        $new->content = $tag['value'];
        return $new;
    }
}
