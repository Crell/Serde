<?php

declare(strict_types=1);

namespace Crell\Serde\Formatter;

use Crell\Serde\Attributes\ClassSettings;
use Crell\Serde\Attributes\Field;
use Crell\Serde\CollectionItem;
use Crell\Serde\DeformatterResult;
use Crell\Serde\Deserializer;
use Crell\Serde\Dict;
use Crell\Serde\Sequence;
use Crell\Serde\Serializer;
use Devium\Toml\Toml;
use Devium\Toml\TomlError;

use function Crell\fp\collect;

class TomlFormatter implements Formatter, Deformatter, SupportsCollecting, SupportsTypeIntrospection
{
    use ArrayBasedFormatter {
        ArrayBasedFormatter::serializeSequence as serializeArraySequence;
        ArrayBasedFormatter::serializeDictionary as serializeArrayDictionary;
    }
    use ArrayBasedDeformatter {
        ArrayBasedDeformatter::deserializeFloat as deserializeArrayFloat;
    }

    public function format(): string
    {
        return 'toml';
    }

    /**
     * @param ClassSettings $classDef
     * @param Field $rootField
     * @return array<string, mixed>
     */
    public function serializeInitialize(ClassSettings $classDef, Field $rootField): array
    {
        return ['root' => []];
    }

    /**
     * @throws TomlError
     */
    public function serializeFinalize(mixed $runningValue, ClassSettings $classDef): string
    {
        return Toml::encode($runningValue['root']);
    }

    /**
     * @param array<string, mixed> $runningValue
     * @param Field $field
     * @param Sequence $next
     * @return array<string, mixed>
     */
    public function serializeSequence(mixed $runningValue, Field $field, Sequence $next, Serializer $serializer): array
    {
        $next->items = array_filter(collect($next->items), static fn(CollectionItem $i) => !is_null($i->value));
        return $this->serializeArraySequence($runningValue, $field, $next, $serializer);
    }

    /**
     * @param array<string, mixed> $runningValue
     * @param Field $field
     * @param Dict $next
     * @return array<string, mixed>
     */
    public function serializeDictionary(mixed $runningValue, Field $field, Dict $next, Serializer $serializer): array
    {
        $next->items = array_filter(collect($next->items), static fn(CollectionItem $i) => !is_null($i->value));
        return $this->serializeArrayDictionary($runningValue, $field, $next, $serializer);
    }

    /**
     * @param mixed $serialized
     * @param ClassSettings $classDef
     * @param Field $rootField
     * @param Deserializer $deserializer
     * @return array<string, mixed>
     * @throws TomlError
     */
    public function deserializeInitialize(
        mixed $serialized,
        ClassSettings $classDef,
        Field $rootField,
        Deserializer $deserializer
    ): array
    {
        return ['root' => Toml::decode($serialized ?: '', true, true)];
    }

    /**
     * TOML in particular frequently uses strings to represent floats, so in that case, cast it like weak mode, always.
     */
    public function deserializeFloat(mixed $decoded, Field $field): float|DeformatterResult|null
    {
        if ($field->phpType === 'float' && is_string($decoded[$field->serializedName]) && is_numeric($decoded[$field->serializedName])) {
            $decoded[$field->serializedName] = (float)$decoded[$field->serializedName];
        }
        return $this->deserializeArrayFloat($decoded, $field);
    }

    public function deserializeFinalize(mixed $decoded): void
    {

    }
}
