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

    public function __construct(
        ?string $name = null,
        ?string $namespace = null,
        ?string $content = null,
        ?array $attributes = null
    ) {
        if (!is_null($name)) {
            $this->name = $name;
        }
        if (!is_null($namespace)) {
            $this->namespace = $namespace;
        }
        if (!is_null($content)) {
            $this->content = $content;
        }
        if (!is_null($attributes)) {
            $this->attributes = $attributes;
        }
    }

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
