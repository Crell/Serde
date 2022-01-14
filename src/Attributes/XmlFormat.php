<?php

declare(strict_types=1);

namespace Crell\Serde\Attributes;

#[\Attribute]
class XmlFormat implements FormatField
{
    public function __construct(
        /** Set to a value to have this field's value be an attribute. Leave null to use the element body. */
        public readonly ?string $attributeName = null,
    ) {}

}
