<?php

declare(strict_types=1);

namespace Crell\Serde\Records;

use Crell\Serde\Attributes\SequenceField;

final readonly class TypeMappedMixedElements
{
    /** @param list<TypeMappedInterface> $elements */
    public function __construct(
        #[SequenceField(arrayType: TypeMappedInterface::class)]
        public array $elements = [],
    ) {
    }
}
