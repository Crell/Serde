<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\DictionaryField;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\SequenceField;
use PHPUnit\Framework\TestCase;

class FieldValidationTest extends TestCase
{
    /**
     * @test
     * @dataProvider fieldValidationExamples()
     */
    public function stuff(string $phpType, mixed $value, ?TypeField $typeField, bool $expected): void
    {
        $f = Field::create('fake', phpType: $phpType, typeField: $typeField);

        self::assertSame($expected, $f->validate($value));
    }

    public function fieldValidationExamples(): iterable
    {
        // Integers.
        yield 'int to int' => [
            'phpType' => 'int',
            'value' => 5,
            'typeField' => null,
            'expected' => true,
        ];
        yield 'string to int' => [
            'phpType' => 'int',
            'value' => 'beep',
            'typeField' => null,
            'expected' => false,
        ];
        yield 'whole-float to int' => [
            'phpType' => 'int',
            'value' => 3.0,
            'typeField' => null,
            'expected' => true,
        ];
        yield 'float to int' => [
            'phpType' => 'int',
            'value' => 3.14,
            'typeField' => null,
            'expected' => false,
        ];
        yield 'array to int' => [
            'phpType' => 'int',
            'value' => [1, 2, 3],
            'typeField' => null,
            'expected' => false,
        ];

        // Floats.
        yield 'float to float' => [
            'phpType' => 'float',
            'value' => 3.14,
            'typeField' => null,
            'expected' => true,
        ];
        yield 'int to float' => [
            'phpType' => 'float',
            'value' => 5,
            'typeField' => null,
            'expected' => true,
        ];
        yield 'string to float' => [
            'phpType' => 'float',
            'value' => 'beep',
            'typeField' => null,
            'expected' => false,
        ];
        yield 'array to float' => [
            'phpType' => 'float',
            'value' => [1, 2, 3],
            'typeField' => null,
            'expected' => false,
        ];

        // Strings.
        yield 'string to string' => [
            'phpType' => 'string',
            'value' => 'beep',
            'typeField' => null,
            'expected' => true,
        ];
        yield 'int to string' => [
            'phpType' => 'string',
            'value' => 5,
            'typeField' => null,
            'expected' => true,
        ];
        yield 'float to string' => [
            'phpType' => 'string',
            'value' => 3.14,
            'typeField' => null,
            'expected' => true,
        ];
        yield 'array to string' => [
            'phpType' => 'string',
            'value' => [1, 2, 3],
            'typeField' => null,
            'expected' => false,
        ];

        // Arrays.
        yield 'array to array' => [
            'phpType' => 'array',
            'value' => [1, 2, 3],
            'typeField' => null,
            'expected' => true,
        ];
        yield 'int to array' => [
            'phpType' => 'array',
            'value' => 5,
            'typeField' => null,
            'expected' => false,
        ];
        yield 'float to array' => [
            'phpType' => 'array',
            'value' => 3.14,
            'typeField' => null,
            'expected' => false,
        ];

        // Sequences.
        yield 'sequence to sequence' => [
            'phpType' => 'array',
            'value' => [1, 2, 3],
            'typeField' => new SequenceField(),
            'expected' => true,
        ];
        yield 'string keys to sequence' => [
            'phpType' => 'array',
            'value' => ['a' => 1, 'b' => 2, 'c' => 3],
            'typeField' => new SequenceField(),
            'expected' => false,
        ];
        yield 'out of order keys to sequence' => [
            'phpType' => 'array',
            'value' => [3 => 1, 2 => 2, 1 => 3],
            'typeField' => new SequenceField(),
            'expected' => false,
        ];
        yield 'numeric string keys to sequence' => [
            'phpType' => 'array',
            'value' => ['0' => 1, '1' => 2, '2' => 3],
            'typeField' => new SequenceField(),
            'expected' => true,
        ];

        // Dictionaries.
        // Numeric string keys get cast to int keys in PHP, so we have to allow this.
        yield 'sequence to dictionary' => [
            'phpType' => 'array',
            'value' => [1, 2, 3],
            'typeField' => new DictionaryField(),
            'expected' => true,
        ];
        yield 'string keys to dictionary' => [
            'phpType' => 'array',
            'value' => ['a' => 1, 'b' => 2, 'c' => 3],
            'typeField' => new DictionaryField(),
            'expected' => true,
        ];
        yield 'out of order keys to dictionary' => [
            'phpType' => 'array',
            'value' => [3 => 1, 2 => 2, 1 => 3],
            'typeField' => new DictionaryField(),
            'expected' => true,
        ];
        yield 'numeric string keys to dictionary' => [
            'phpType' => 'array',
            'value' => ['0' => 1, '1' => 2, '2' => 3],
            'typeField' => new DictionaryField(),
            'expected' => true,
        ];
        // Numeric string keys get cast to int keys in PHP, so we have to allow this.
        yield 'numeric string keys to string-dictionary' => [
            'phpType' => 'array',
            'value' => ['0' => 1, '1' => 2, '2' => 3],
            'typeField' => new DictionaryField(keyType: KeyType::String),
            'expected' => true,
        ];
        yield 'string keys to string-dictionary' => [
            'phpType' => 'array',
            'value' => ['a' => 1, 'b' => 2, 'c' => 3],
            'typeField' => new DictionaryField(keyType: KeyType::String),
            'expected' => true,
        ];
        yield 'string keys to numeric-dictionary' => [
            'phpType' => 'array',
            'value' => ['a' => 1, 'b' => 2, 'c' => 3],
            'typeField' => new DictionaryField(keyType: KeyType::Int),
            'expected' => false,
        ];
        yield 'mixed keys to numeric-dictionary' => [
            'phpType' => 'array',
            'value' => [0 => 1, 'b' => 2, 'c' => 3],
            'typeField' => new DictionaryField(keyType: KeyType::Int),
            'expected' => false,
        ];
        yield 'numeric keys to numeric-dictionary' => [
            'phpType' => 'array',
            'value' => [3 => 1, 2 => 2, 1 => 3],
            'typeField' => new DictionaryField(keyType: KeyType::Int),
            'expected' => true,
        ];
    }
}
