<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Formatter\Deformatter;
use Crell\Serde\Formatter\SupportsCollecting;
use Crell\Serde\PropertyHandler\PropertyReader;
use Crell\Serde\PropertyHandler\PropertyWriter;
use function Crell\fp\first;
use function Crell\fp\pipe;

// This exists mainly just to create a closure over the format and formatter.
// But that does simplify a number of functions.
class Deserializer
{
    public function __construct(
        protected readonly ClassAnalyzer $analyzer,
        /** @var PropertyReader[]  */
        protected readonly array $readers,
        /** @var PropertyWriter[] */
        protected readonly array $writers,
        protected readonly Deformatter $formatter,
    ) {}


    public function deserialize(mixed $decoded, string $targetType): mixed
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($targetType, ClassDef::class);

        $props = [];
        $usedNames = [];
        $collectingField = null;

        // Build up an array of properties that we can then assign all at once.
        foreach ($objectMetadata->properties as $field) {
            $usedNames[] = $field->serializedName;
            if ($field->flatten) {
                $collectingField = $field;
            } else {
                $value = $this->deserializeValue($field, $decoded);
                if ($value === SerdeError::Missing) {
                    if ($field->shouldUseDefault) {
                        $value = $field->defaultValue;
                        $props[$field->phpName] = $value;
                    }
                } else {
                    $props[$field->phpName] = $value;
                }
            }
        }

        if ($collectingField && $this->formatter instanceof SupportsCollecting) {
            $remaining = $this->formatter->getRemainingData($decoded, $usedNames);
            if ($collectingField->phpType === 'array') {
                foreach ($remaining as $k => $v) {
                    $f = Field::create(serializedName: $k, phpName: $k, phpType: \get_debug_type($v));
                    $props[$collectingField->phpName][$k] = $this->deserializeValue($f, $remaining);
                }
            }
            // @todo Do we support collecting into objects? Does that even make sense?
        }

        // @todo What should happen if something is still set to Missing?
        $rClass = new \ReflectionClass($targetType);
        $new = $rClass->newInstanceWithoutConstructor();


        $populate = function (array $props) {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        };
        $populate->bindTo($new, $new)($props);
        return $new;
    }

    protected function deserializeValue(Field $field, mixed $source): mixed
    {
        // @todo Better exception.
        /** @var PropertyWriter $writer */
        $writer =
            pipe($this->writers, first(fn (PropertyWriter $w): bool => $w->canWrite($field, $this->formatter->format())))
            ?? throw new \RuntimeException('No writer for ' . $field->phpType);

        return $writer->writeValue($this->formatter, $this->deserialize(...), $field, $source);
    }
}
