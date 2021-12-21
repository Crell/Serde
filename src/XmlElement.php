<?php

declare(strict_types=1);

namespace Crell\Serde;

class XmlElement
{
    public readonly string $name;
    public readonly string $namespace;
    public readonly string $content;
    public readonly array $attributes;
    /**
     * @var array<string, static>
     */
    public array $children = [];

    public static function fromTag(array $tag): static
    {
        $new = new static();
        $new->name = $tag['name'];
        $new->namespace = $tag['namespace'];
        $new->attributes = $tag['attributes'];
        $new->content = $tag['value'];
        return $new;
    }
}
