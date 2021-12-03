<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use function Crell\fp\first;
use function Crell\fp\pipe;

class TypeMapper
{
    public function __construct(
        /** TypeMap[] */
        protected readonly array $typeMaps,
        protected readonly ClassAnalyzer $analyzer,
    ) {}

    public function typeMap(Field $field): ?TypeMap
    {
        if (!in_array($field->typeCategory, [TypeCategory::Object, TypeCategory::Array], true)) {
            // @todo Better exception.
            throw new \RuntimeException('Can only get class for a class Field.');
        }

        $map = pipe($this->typeMaps,
            first(static fn (TypeMap $map, string $class) => is_a($field->phpType, $class, true)),
        );

        if ($map) {
            return $map;
        }

        if ($field->typeMap) {
            return $field->typeMap;
        }

        // Transitivity ought to make this block unnecessary, but seemingly doesn't.
        // @todo Figure out why and fix it.
        if (class_exists($field->phpType) || interface_exists($field->phpType)) {
            $classDef = $this->analyzer->analyze($field->phpType, ClassDef::class);
            if ($classDef?->typeMap) {
                return $classDef->typeMap;
            }
        }

        return null;
    }

    public function getClassFor(Field $field): string
    {
        if ($field->typeCategory !== TypeCategory::Object) {
            // @todo Better exception.
            throw new \RuntimeException('Can only get class for a class Field.');
        }


    }
}
