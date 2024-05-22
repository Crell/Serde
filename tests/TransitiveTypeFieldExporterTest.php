<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\Field;
use Crell\Serde\Formatter\ArrayFormatter;
use Crell\Serde\PropertyHandler\Exporter;
use Crell\Serde\PropertyHandler\Importer;
use Crell\Serde\Records\Transitive;
use Crell\Serde\Records\TransitiveExample;
use Crell\Serde\Records\Transitives;
use Crell\Serde\Records\TransitiveTypeField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TransitiveTypeFieldExporterTest extends TestCase
{
    #[Test]
    public function can_export_the_right_values(): void
    {
        $exporter = new class () implements Exporter {
            public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
            {
                return $serializer->formatter->serializeString($runningValue, $field, $value->name);
            }

            public function canExport(Field $field, mixed $value, string $format): bool
            {
                return $field->typeField instanceof TransitiveTypeField;
            }
        };
        
        $serde = new SerdeCommon(handlers: [$exporter], formatters: [new ArrayFormatter()]);

        $expected = ['transitive' => 'exported transitive type field'];
        $actual = $serde->serialize(new TransitiveExample(new Transitive('exported transitive type field')), format: 'array');

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function can_import_the_right_values(): void
    {
        $importer = new class () implements Importer {
            public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
            {
                $value = $deserializer->deformatter->deserializeDictionary($source, $field, $deserializer);

                return new TransitiveExample(new Transitive($value['name']));
            }

            public function canImport(Field $field, string $format): bool
            {
                return $field->typeField instanceof TransitiveTypeField;
            }
        };
        
        $serde = new SerdeCommon(handlers: [$importer], formatters: [new ArrayFormatter()]);

        $expected = new TransitiveExample(new Transitive('imported transitive type field'));
        $actual = $serde->deserialize(['transitive' => ['name' => 'imported transitive type field']], from: 'array', to: TransitiveExample::class);

        $this->assertEquals($expected, $actual);
    }
}
