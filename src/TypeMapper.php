<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use function Crell\fp\firstWithKeys;
use function Crell\fp\pipe;

class TypeMapper
{
    public function __construct(
        /** @var array<class-string, TypeMap> */
        protected readonly array $typeMaps,
        protected readonly ClassAnalyzer $analyzer,
    ) {}

    public function typeMapForField(Field $field): ?TypeMap
    {
        if (!in_array($field->typeCategory, [TypeCategory::Object, TypeCategory::Array], true)) {
            throw TypeMapOnNonObjectField::create($field);
        }

        return $this->getOverrideMapFor($field->phpType)
            ?? $field->typeMap
            ?? $this->typeMapForClass($field->phpType);
    }

    public function typeMapForClass(string $class): ?TypeMap
    {
        if (!class_exists($class) && !interface_exists($class)) {
            return null;
        }

        return $this->getOverrideMapFor($class)
            ?? $this->analyzer->analyze($class, ClassSettings::class)->typeMap;
    }

    /**
     * @param array<mixed> $data
     * @return class-string|null
     */
    public function getTargetClass(Field $field, array $data): ?string
    {
        if ($field->typeCategory !== TypeCategory::Object) {
            throw TypeMapOnNonObjectField::create($field);
        }

        if (!$map = $this->typeMapForField($field)) {
            // @phpstan-ignore-next-line
            return $field->phpType;
        }

        if (! $key = ($data[$map->keyField()] ?? null)) {
            return null;
        }

        if (!$class = $map->findClass($key)) {
            throw NoTypeMapDefinedForKey::create($key, $field->phpName ?? $field->phpType);
        }

        return $class;
    }

    /**
     * Gets the property list for a given object.
     *
     * We need to know the object properties to deserialize to.
     * However, that list may be modified by the type map, as
     * the type map is in the incoming data.
     * The key field is kept in the data so that the property writer
     * can also look up the right type.
     *
     * @param Field $field
     * @param array<mixed> $data
     * @return Field[]
     */
    public function propertyList(Field $field, array $data): array
    {
        $class = $this->getTargetClass($field, $data);

        return $class ?
            $this->analyzer->analyze($class, ClassSettings::class)->properties
            : [];
    }

    protected function getOverrideMapFor(string $class): ?TypeMap
    {
        return pipe($this->typeMaps,
            firstWithKeys(static fn (TypeMap $map, string $overrideClass) => is_a($class, $overrideClass, true)),
        );
    }
}
