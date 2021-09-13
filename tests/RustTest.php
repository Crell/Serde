<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\ClassAnalyzer;
use Crell\Serde\Records\Point;
use PHPUnit\Framework\TestCase;

class RustTest extends TestCase
{
    /**
     * @test
     */
    public function point(): void
    {
        $s = new RustSerializer();

        $p1 = new Point(1, 2, 3);

        $json = $s->serialize($p1, 'json');

        self::assertEquals('{"x":1,"y":2,"z":3}', $json);

        $result = $s->deserialize($json, from: 'json', to: Point::class);

        self::assertEquals($p1, $result);
    }
}

class RustSerializer
{
    protected ClassAnalyzer $analyzer;
    public function __construct()
    {
        $this->analyzer = new Analyzer();
    }

    public function serialize(object $o, string $format): string
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($o, ClassDef::class);

        $formatter = new JsonFormatter();
        $runningValue = $formatter->initialize();
        foreach ($objectMetadata->properties as $field) {
            $name = $field->name;
            $name = $this->mangle($name);

            $runningValue = match ($field->phpType) {
                'int' => $formatter->serializeInt($runningValue, $name, $o->{$field->name}),
                'float' => $formatter->serializeFloat($runningValue, $name, $o->{$field->name}),
                'bool' => $formatter->serializeBool($runningValue, $name, $o->{$field->name}),
                'string' => $formatter->serializeString($runningValue, $name, $o->{$field->name}),
                'array' => $formatter->serializeArray($runningValue, $name, $o->{$field->name}),
                'object' => $formatter->serializeObject($runningValue, $name, $o->{$field->name}),
//                'DateTime' => $formatter->translateDateTime($runningValue, $name, $o->{$field->name}),
            };
        }

        return $formatter->finalize($runningValue);
    }

    public function deserialize(string $serialized, string $from, string $to): object
    {
        /** @var ClassDef $objectMetadata */
        $objectMetadata = $this->analyzer->analyze($to, ClassDef::class);

        $formatter = new JsonFormatter();

        $props = [];

        $decoded = $formatter->deserializeInitialize($serialized);

        // Build up an array of properties that we can then assign all at once.
        foreach ($objectMetadata->properties as $field) {
            $name = $field->name;
            $name = $this->mangle($name);

            $props[$field->name] = match ($field->phpType) {
                'int' => $formatter->deserializeInt($decoded, $name),
                'float' => $formatter->deserializeFloat($decoded, $name),
                'bool' => $formatter->deserializeBool($decoded, $name),
                'string' => $formatter->deserializeString($decoded, $name),
                'array' => $formatter->deserializeArray($decoded, $name),
                'object' => $formatter->deserializeObject($decoded, $name),
//                'DateTime' => $formatter->translateDateTime($runningValue, $name, $o->{$field->name}),
            };
        }

        $populate = function (array $props) {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        };

        $new = (new \ReflectionClass($to))->newInstanceWithoutConstructor();
        $populate->bindTo($new, $new)($props);
        return $new;

    }

    protected function mangle(string $name): string
    {
        return $name;
    }
}

class JsonFormatter
{
    public function initialize(): mixed
    {
        return [];
    }

    public function finalize(mixed $val): string
    {
        return json_encode($val, JSON_THROW_ON_ERROR);
    }

    public function serializeInt(mixed $val, string $name, mixed $next): mixed
    {
        $val[$name] = $next;
        return $val;
    }
    public function serializeFloat(mixed $val, string $name, mixed $next): mixed
    {
        $val[$name] = $next;
        return $val;
    }
    public function serializeString(mixed $val, string $name, mixed $next): mixed
    {
        $val[$name] = $next;
        return $val;
    }
    public function serializeBool(mixed $val, string $name, mixed $next): mixed
    {
        $val[$name] = $next;
        return $val;
    }
    public function serializeArray(mixed $val, string $name, mixed $next): mixed
    {
        $val[$name] = $next;
        return $val;
    }
    public function serializeObject(mixed $val, string $name, mixed $next): mixed
    {
        $val[$name] = $next;
        return $val;
    }

    public function deserializeInitialize(string $serialized): mixed
    {
        return json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
    }
    public function deserializeInt(mixed $decoded, string $name): int
    {
        return $decoded[$name];
    }
    public function deserializeFloat(mixed $decoded, string $name): int
    {
        return $decoded[$name];
    }
    public function deserializeBool(mixed $decoded, string $name): int
    {
        return $decoded[$name];
    }
    public function deserializeString(mixed $decoded, string $name): int
    {
        return $decoded[$name];
    }
    public function deserializeArray(mixed $decoded, string $name): int
    {
        return $decoded[$name];
    }
    public function deserializeObject(mixed $decoded, string $name): int
    {
        return $decoded[$name];
    }
}
