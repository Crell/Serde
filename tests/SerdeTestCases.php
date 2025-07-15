<?php

declare(strict_types=1);

namespace Crell\Serde;

use Crell\Serde\Attributes\ClassNameTypeMap;
use Crell\Serde\Attributes\Enums\UnixTimeResolution;
use Crell\Serde\Attributes\Field;
use Crell\Serde\Attributes\ReducingTransitiveTypeField;
use Crell\Serde\Attributes\StaticTypeMap;
use Crell\Serde\Attributes\TransitiveTypeField;
use Crell\Serde\Formatter\SupportsCollecting;
use Crell\Serde\PropertyHandler\Exporter;
use Crell\Serde\PropertyHandler\ObjectExporter;
use Crell\Serde\PropertyHandler\ObjectImporter;
use Crell\Serde\Records\ACT;
use Crell\Serde\Records\AliasedFields;
use Crell\Serde\Records\AllFieldTypes;
use Crell\Serde\Records\BackedSize;
use Crell\Serde\Records\Callbacks\CallbackHost;
use Crell\Serde\Records\CircularReference;
use Crell\Serde\Records\ClassWithDefaultRenaming;
use Crell\Serde\Records\ClassWithInterfaces;
use Crell\Serde\Records\ClassWithPropertyWithTransitiveTypeField;
use Crell\Serde\Records\ClassWithReducibleProperty;
use Crell\Serde\Records\CompoundTypes;
use Crell\Serde\Records\DateTimeExample;
use Crell\Serde\Records\DictionaryKeyTypes;
use Crell\Serde\Records\Drupal\EmailItem;
use Crell\Serde\Records\Drupal\FieldItemList;
use Crell\Serde\Records\Drupal\LinkItem;
use Crell\Serde\Records\Drupal\Node;
use Crell\Serde\Records\Drupal\StringItem;
use Crell\Serde\Records\Drupal\TextItem;
use Crell\Serde\Records\EmptyData;
use Crell\Serde\Records\ExcludeNullFields;
use Crell\Serde\Records\ExcludeNullFieldsClass;
use Crell\Serde\Records\Exclusions;
use Crell\Serde\Records\ExplicitDefaults;
use Crell\Serde\Records\FlatCollection\FlatThing;
use Crell\Serde\Records\FlatCollection\FlatThingList;
use Crell\Serde\Records\FlatCollection\FlatThingMap;
use Crell\Serde\Records\FlatMapNested\HostObject;
use Crell\Serde\Records\FlatMapNested\Item;
use Crell\Serde\Records\FlatMapNested\NestedA;
use Crell\Serde\Records\FlattenedNullableMain;
use Crell\Serde\Records\Flattening;
use Crell\Serde\Records\ImplodingArrays;
use Crell\Serde\Records\InvalidFieldType;
use Crell\Serde\Records\Iterables;
use Crell\Serde\Records\MangleNames;
use Crell\Serde\Records\MappedCollected\ThingA;
use Crell\Serde\Records\MappedCollected\ThingB;
use Crell\Serde\Records\MappedCollected\ThingC;
use Crell\Serde\Records\MappedCollected\ThingList;
use Crell\Serde\Records\MixedVal;
use Crell\Serde\Records\MixedValObject;
use Crell\Serde\Records\MultiCollect\ThingOneA;
use Crell\Serde\Records\MultiCollect\ThingTwoC;
use Crell\Serde\Records\MultiCollect\Wrapper;
use Crell\Serde\Records\MultipleScopes;
use Crell\Serde\Records\MultipleScopesDefaultTrue;
use Crell\Serde\Records\NativeSerUn;
use Crell\Serde\Records\NestedFlattenObject;
use Crell\Serde\Records\NestedObject;
use Crell\Serde\Records\NonPromotedDefault;
use Crell\Serde\Records\NullArrays;
use Crell\Serde\Records\NullProps;
use Crell\Serde\Records\OptionalPoint;
use Crell\Serde\Records\Pagination\DetailedResults;
use Crell\Serde\Records\Pagination\NestedPagination;
use Crell\Serde\Records\Pagination\Pagination;
use Crell\Serde\Records\Pagination\PaginationState;
use Crell\Serde\Records\Pagination\Product;
use Crell\Serde\Records\Pagination\ProductType;
use Crell\Serde\Records\Pagination\Results;
use Crell\Serde\Records\Point;
use Crell\Serde\Records\ReducibleClass;
use Crell\Serde\Records\RequiresFieldValues;
use Crell\Serde\Records\RequiresFieldValuesClass;
use Crell\Serde\Records\RootMap\Type;
use Crell\Serde\Records\RootMap\TypeB;
use Crell\Serde\Records\SAT;
use Crell\Serde\Records\ScalarArrays;
use Crell\Serde\Records\SequenceOfStrings;
use Crell\Serde\Records\Shapes\Box;
use Crell\Serde\Records\Shapes\Circle;
use Crell\Serde\Records\Shapes\Rectangle;
use Crell\Serde\Records\Shapes\Shape;
use Crell\Serde\Records\Shapes\ShapeList;
use Crell\Serde\Records\Shapes\TwoDPoint;
use Crell\Serde\Records\Size;
use Crell\Serde\Records\Tasks\BigTask;
use Crell\Serde\Records\Tasks\SmallTask;
use Crell\Serde\Records\Tasks\Task;
use Crell\Serde\Records\Tasks\TaskContainer;
use Crell\Serde\Records\TransitiveField;
use Crell\Serde\Records\TraversableInts;
use Crell\Serde\Records\TraversablePoints;
use Crell\Serde\Records\Traversables;
use Crell\Serde\Records\UnionTypeSubTypeField;
use Crell\Serde\Records\UnionTypeWithInterface;
use Crell\Serde\Records\UnixTimeExample;
use Crell\Serde\Records\ValueObjects\Age;
use Crell\Serde\Records\ValueObjects\Email;
use Crell\Serde\Records\ValueObjects\JobDescription;
use Crell\Serde\Records\ValueObjects\JobEntry;
use Crell\Serde\Records\ValueObjects\JobEntryFlattened;
use Crell\Serde\Records\ValueObjects\JobEntryFlattenedPrefixed;
use Crell\Serde\Records\ValueObjects\Person;
use Crell\Serde\Records\Visibility;
use Crell\Serde\Records\WeakLists;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Crell\Serde\Records\UnionTypes;

/**
 * Testing base class.
 *
 * To test a specific formatter:
 *
 * - Extend this class.
 * - In setUp(), set the $formatters and $format property accordingly.
 * - Optionally define a <method name>_validate($serialized) method to introspect
 *   the serialized data for that test in a format-specific way.
 */
abstract class SerdeTestCases extends TestCase
{
    protected array $formatters;

    protected string $format;

    /**
     * Whatever the "empty string" equivalent is for a given format.
     */
    protected mixed $emptyData;

    /**
     * A serialized blob with aliased fields.
     *
     * @see field_aliases_read_on_deserialize()
     */
    protected mixed $aliasedData;

    /**
     * A serialized value for the DictionaryKeyTypes that has a string key where it should be an int.
     *
     * @see dictionary_key_string_in_int_throws_on_deserialize()
     */
    protected mixed $invalidDictStringKey;

    /**
     * A serialized value for the DictionaryKeyTypes that has an int key where it should be a string.
     *
     * @see dictionary_key_int_in_string_throws_in_deserialize()
     */
    protected mixed $invalidDictIntKey;

    /**
     * Data that is missing a required field for which a default is provided.
     *
     * @see missing_required_value_with_default_does_not_throw()
     */
    protected mixed $missingOptionalData;

    /**
     * Data to deserialize that should fail in strict mode, because a strict sequence cannot take a dict.
     * @see non_sequence_arrays_in_strict_mode_throw()
     */
    protected mixed $dictsInSequenceShouldFail;

    /**
     * Data to deserialize that contains numeric-string lists, which should still coerce into an integer list safely.
     *
     * @see lists_in_weak_mode_coerce_elements())
     */
    protected mixed $weakModeLists;

    /**
     * Data to deserialize that should pass, because the strict is valid and non-strict gets coerced to a list.
     * @see non_sequence_arrays_in_weak_mode_are_coerced
     */
    protected mixed $dictsInSequenceShouldPass;

    abstract protected function arrayify(mixed $serialized): array;

    protected function validateSerialized(mixed $serialized, string $name): void
    {
        $validateMethod = str_replace([' ', ':', ','], '_', $name) . '_validate';
        if (method_exists($this, $validateMethod)) {
            $this->$validateMethod($serialized);
        }
    }

    #[Test]
    #[DataProvider('round_trip_examples')]
    #[DataProvider('value_object_flatten_examples')]
    #[DataProvider('mixed_val_property_examples')]
    #[DataProvider('mixed_val_property_object_examples')]
    #[DataProvider('union_types_examples')]
    #[DataProvider('compound_types_examples')]
    public function round_trip(object $data): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $serialized = $s->serialize($data, $this->format);

        // dataName() is marked internal, but seems stable enough.
        // This avoids making each data set provide an extra name string
        // just for here, when they should already all have a name on
        // the dataset to begin with.
        $this->validateSerialized($serialized, (string)$this->dataName());

        $result = $s->deserialize($serialized, from: $this->format, to: $data::class);

        self::assertEquals($data, $result);
    }

    #[Test, DataProvider('round_trip_flattening_examples'), Group('flattening')]
    public function round_trip_flattening(object $data): void
    {
        foreach ($this->formatters as $formatter) {
            if (($formatter->format() === $this->format) && !$formatter instanceof SupportsCollecting) {
                $this->markTestSkipped('Skipping flattening tests on non-flattening formatters');
            }
        }

        $s = new SerdeCommon(formatters: $this->formatters);

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, (string)$this->dataName());

        $result = $s->deserialize($serialized, from: $this->format, to: $data::class);

        self::assertEquals($data, $result);
    }

    public static function round_trip_examples(): iterable
    {
        yield 'point' => [
            'data' => new Point(1, 2, 3),
        ];
        yield 'visibility' => [
            'data' => new Visibility(1, 2, 3, new Visibility(4, 5, 6)),
        ];
        yield 'optional_point' => [
            'data' => new OptionalPoint(1, 2),
        ];
        yield 'all_fields' => [
            'data' => new AllFieldTypes(
                anint: 5,
                string: 'hello',
                afloat: 3.14,
                bool: true,
                dateTimeImmutable: new \DateTimeImmutable('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
                dateTime: new \DateTime('2021-05-01 08:30:45', new \DateTimeZone('America/Chicago')),
                dateTimeZone: new \DateTimeZone('America/Chicago'),
                simpleArray: ['a', 'b', 'c', 1, 2, 3],
                assocArray: ['a' => 'A', 'b' => 'B', 'c' => 'C'],
                simpleObject: new Point(4, 5, 6),
                objectList: [new Point(1, 2, 3), new Point(4, 5, 6)],
                objectMap: ['a' => new Point(1, 2, 3), 'b' => new Point(4, 5, 6)],
                nestedArray: [
                    'a' => [1, 2, 3],
                    'b' => ['a' => 1, 'b' => 2, 'c' => 3],
                    'c' => 'normal',
                    // I don't think this is even possible to support on deserialization,
                    // as there is nowhere to inject the necessary type information.
                    //'d' => [new Point(1, 2, 3), new Point(4, 5, 6)],
                ],
                size: Size::Large,
                backedSize: BackedSize::Large,
                implodedSeq: [1, 2, 3],
                implodedDict: ['a' => 'A', 'b' => 'B'],
//            untyped: 'beep',
            ),
        ];
        yield 'float_fields_take_ints' => [
            'data' => new AllFieldTypes(afloat: 5.0),
        ];
        yield 'name_mangling' => [
            'data' => new MangleNames(
                customName: 'Larry',
                toUpper: 'value',
                toLower: 'value',
                prefix: 'value',
            ),
        ];
        yield 'nested_objects' => [
            'data' => new NestedObject(
                'First',
                new NestedObject(
                    'Second',
                    new NestedObject(
                        'Third',
                        new NestedObject('Fourth')
                    )
                )
            ),
        ];
        yield 'empty_values' => [
            'data' => new EmptyData('beep', null),
        ];
        yield 'native_object_serialization' => [
            'data' => new NativeSerUn(
                1,
                'beep',
                new \DateTimeImmutable('1918-11-11 11:11:11', new \DateTimeZone('America/Chicago'))
            ),
        ];
        yield 'dictionary_key' => [
            'data' => new DictionaryKeyTypes(
                stringKey: ['a' => 'A', 'b' => 'B'],
                intKey: [5 => 'C', 10 => 'D'],
            ),
        ];
        yield 'array_of_null_serializes_cleanly' => [
            'data' => new NullArrays(),
        ];
        yield 'class_level_renaming_applies' => [
            'data' => new ClassWithDefaultRenaming(string: 'B', int: 12),
        ];
        yield 'null_properties_may_be_excluded' => [
            'data' => new ExcludeNullFields('A'),
        ];
        yield 'null_properties_may_be_excluded_class_level' => [
            'data' => new ExcludeNullFieldsClass('A'),
        ];
        yield 'arrays_with_valid_scalar_values' => [
            'data' => new ScalarArrays(
                ints: [1, 2, 3],
                floats: [3.14, 2.7],
                stringMap: ['a' => 'A'],
                arrayMap: ['a' => [1, 2, 3]],
            ),
        ];
    }

    public static function value_object_flatten_examples(): \Generator
    {
        // This set is for ensuring value objects can flatten cleanly.
        yield 'value_objects_with_similar_property_names_work' => [
            'data' => new Person('Larry', new Age(21), new Email('me@example.com')),
        ];
        yield 'multiple_same_class_value_objects_work' => [
            'data' => new JobDescription(new Age(18), new Age(65)),
        ];
        yield 'multiple_same_class_value_objects_work_when_nested' => [
            'data' => new JobEntry(new JobDescription(new Age(18), new Age(65))),
        ];
        yield 'multiple_same_class_value_objects_work_when_nested_and_flattened' => [
            'data' => new JobEntryFlattened(new JobDescription(new Age(18), new Age(65))),
        ];
        yield 'multiple_same_class_value_objects_work_when_nested_and_flattened_with_prefix' => [
            'data' => new JobEntryFlattenedPrefixed(new JobDescription(new Age(18), new Age(65))),
        ];
    }

    public static function mixed_val_property_examples(): iterable
    {
        yield 'mixed val: string' => [
            'data' => new MixedVal('hello'),
        ];
        yield 'mixed val: int' => [
            'data' => new MixedVal(5),
        ];
        yield 'mixed val: float' => [
            'data' => new MixedVal(3.14),
        ];
        yield 'mixed val: sequence' => [
            'data' => new MixedVal(['a', 'b', 'c']),
        ];
        yield 'mixed val: dict' => [
            'data' => new MixedVal(['a' => 'A', 'b' => 'B', 'c' => 'C']),
        ];
    }

    public static function mixed_val_property_object_examples(): iterable
    {
        yield 'mixed val, object: string' => [
            'data' => new MixedValObject('hello'),
        ];
        yield 'mixed val, object: int' => [
            'data' => new MixedValObject(5),
        ];
        yield 'mixed val, object: float' => [
            'data' => new MixedValObject(3.14),
        ];
        yield 'mixed val, object: object' => [
            'data' => new MixedValObject(new Point(1, 2, 3)),
        ];
    }

    public static function union_types_examples(): iterable
    {
        yield 'union: all primitives' => [
            'data' => new UnionTypes(5, 3.14, 'point', 'email'),
        ];
        yield 'union: object and string' => [
            'data' => new UnionTypes('five', 3, new Point(1, 2, 3), 'email'),
        ];
        yield 'union: property with 2 classes' => [
            'data' => new UnionTypes('five', 3, new Point(1, 2, 3), new Email('email@example.com')),
        ];
        yield 'union: union with interface, with int' => [
            'data' => new UnionTypeWithInterface(99),
        ];
        yield 'union: union with interface, with ACT' => [
            'data' => new UnionTypeWithInterface(new ACT(30)),
        ];
        yield 'union: union with interface, with SAT' => [
            'data' => new UnionTypeWithInterface(new SAT(1300)),
        ];
        yield 'union: union with sub-typefield, with string' => [
            'data' => new UnionTypeSubTypeField('hello'),
        ];
        yield 'union: union with sub-typefield, with array' => [
            'data' => new UnionTypeSubTypeField(['hello' => new Point(1, 2, 3)]),
        ];
    }

    public static function compound_types_examples(): iterable
    {
        yield 'string' => [
            'data' => new CompoundTypes('foo'),
        ];
        yield 'intersection type' => [
            'data' => new CompoundTypes(new ClassWithInterfaces('a')),
        ];
    }

    /**
     * These all relate to flattening, so only work on some formatters.
     */
    public static function round_trip_flattening_examples(): iterable
    {
        yield 'flat_thing_list' => [
            'data' => new FlatThingList(
                new Point(1,1, 3),
                new Point(1,2, 4),
                new Point(2,1, 5),
            ),
        ];
        yield 'flat_thing_map' => [
            'data' => new FlatThingMap(
                first: new Point(1,1, 3),
                second: new Point(1,2, 3),
                third: new Point(2,1, 5),
            ),
        ];
        yield 'flattening' => [
            'data' => new Flattening(
                first: 'Larry',
                last: 'Garfield',
                other: ['a' => 'A', 'b' => 2, 'c' => 'C'],
            ),
        ];
        yield 'mapped_collected_dictionary' => [
            'data' => new ThingList(name: 'list', things: [
                'A' => new ThingA('a', 'b'),
                'B' => new ThingB('d', 'd'),
                'C' => new ThingC('e', 'f'),
            ]),
        ];
        yield 'mapped_collected_sequence' => [
            'data' => new ThingList(name: 'list', things: [
                new ThingA('a', 'b'),
                new ThingB('d', 'd'),
                new ThingC('e', 'f'),
            ]),
        ];
        yield 'pagination_flatten_object' => [
            'data' => new Results(
                pagination: new Pagination(
                    total: 500,
                    offset: 40,
                    limit: 10,
                ),
                products: [
                    new Product('Widget', 4.95),
                    new Product('Gadget', 99.99),
                    new Product('Dohickey', 11.50),
                ]
            ),
        ];
        yield 'pagination_flatten_multiple_object' => [
            'data' => new DetailedResults(
                pagination: new NestedPagination(
                    total: 500,
                    limit: 10,
                    state: new PaginationState(40),
                ),
                type: new ProductType(
                    name: 'Beep',
                    category: 'Boop'
                ),
                products: [
                    new Product('Widget', 4.95),
                    new Product('Gadget', 99.99),
                    new Product('Dohickey', 11.50),
                ],
                other: ['narf' => 'poink', 'bleep' => 'bloop']
            ),
        ];
    }

    /**
     * This tests an empty object value, which means something different in different formats.
     */
    #[Test]
    public function empty_input(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $serialized = $this->emptyData;

        $result = $s->deserialize($serialized, from: $this->format, to: AllFieldTypes::class);

        self::assertEquals(new AllFieldTypes(), $result);
    }

    /**
     * This tests an empty string of input.
     */
    #[Test]
    public function empty_string(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $serialized = '';

        $result = $s->deserialize($serialized, from: $this->format, to: AllFieldTypes::class);

        self::assertEquals(new AllFieldTypes(), $result);
    }

    #[Test]
    public function matching_constructor_param_only_uses_default_if_promoted(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $serialized = $this->emptyData;

        $result = $s->deserialize($serialized, from: $this->format, to: NonPromotedDefault::class);

        self::assertEquals('not set', $result->value ?? 'not set');
    }

    #[Test, Group('typemap')]
    public function static_typemap(): void
    {
        $typeMap = new StaticTypeMap(key: 'size', map: [
            'big' => BigTask::class,
            'small' => SmallTask::class,
        ]);

        $s = new SerdeCommon(
            formatters: $this->formatters,
            typeMaps: [Task::class => $typeMap],
        );

        $data = new TaskContainer(
            task: new BigTask('huge'),
        );

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: TaskContainer::class);

        self::assertEquals($data, $result);
    }

    #[Test, Group('typemap')]
    public function dynamic_type_map(): void
    {
        $typeMap = new class implements TypeMap {
            public function keyField(): string
            {
                return 'size';
            }

            public function findClass(string $id): string
            {
                // Or do a DB lookup or whatever.
                return match ($id) {
                    'small' => SmallTask::class,
                    'big' => BigTask::class,
                };
            }

            public function findIdentifier(string $class): string
            {
                return match ($class) {
                    SmallTask::class => 'small',
                    BigTask::class => 'big',
                };
            }
        };

        $s = new SerdeCommon(formatters: $this->formatters, typeMaps: [
            Task::class => $typeMap,
        ]);

        $data = new TaskContainer(
            task: new BigTask('huge'),
        );

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: TaskContainer::class);

        self::assertEquals($data, $result);
    }

    #[Test, Group('typemap')]
    public function typemap_on_parent_class(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new Box(new Circle(new TwoDPoint(1, 2), 3));

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: Box::class);

        self::assertEquals($data, $result);
    }

    #[Test, Group('typemap')]
    public function classname_typemap(): void
    {
        $typeMap = new ClassNameTypeMap(key: 'class');

        $s = new SerdeCommon(formatters: $this->formatters, typeMaps: [
            Shape::class => $typeMap,
        ]);

        $data = new Box(new Circle(new TwoDPoint(1, 2), 3));

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: Box::class);

        self::assertEquals($data, $result);
    }

    #[Test]
    public function circular_detection(): void
    {
        $this->expectException(CircularReferenceDetected::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        $a = new CircularReference('A');
        $b = new CircularReference('B');
        $c = new CircularReference('C');
        $a->ref = $b;
        $b->ref = $c;
        $c->ref = $a;

        // This should throw an exception when the loop is detected.
        $serialized = $s->serialize($a, $this->format);
    }

    #[Test, Group('typemap')]
    public function root_type_map(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new TypeB('Bob');

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: Type::class);

        self::assertEquals($data, $result);
    }

    #[Test, Group('typemap')]
    public function bad_type_map(): void
    {
        $this->expectException(NoTypeMapDefinedForKey::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        $array = [
            'type' => 'c',
            'name' => 'Carl',
        ];

        // This should throw an exception because there is no mapping for type 'c'.
        $s->deserialize($array, from: 'array', to: Type::class);
    }

    #[Test, Group('typemap')]
    public function nested_objects_with_flattening(): void
    {
        foreach ($this->formatters as $formatter) {
            if (($formatter->format() === $this->format) && !$formatter instanceof SupportsCollecting) {
                $this->markTestSkipped('Skipping flattening tests on non-flattening formatters');
            }
        }

        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new NestedFlattenObject('First', ['a' => 'A', 'b' => 'B'],
            new NestedFlattenObject('Second', ['a' => 'A', 'b' => 'B'],
                new NestedFlattenObject('Third', ['a' => 'A', 'b' => 'B'],
                    new NestedFlattenObject('Fourth', ['a' => 'A', 'b' => 'B']))));

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: NestedFlattenObject::class);

        self::assertEquals($data, $result);
    }

    #[Test]
    public function exclude_values(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new Exclusions('one', 'two');

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        /** @var Exclusions $result */
        $result = $s->deserialize($serialized, from: $this->format, to: Exclusions::class);

        self::assertEquals('one', $result->one);
        self::assertNull($result->two ?? null);
    }

    #[Test, Group('typemap')]
    public function drupal_example(): void
    {
        $typeMap = new class implements TypeMap {
            public function keyField(): string
            {
                return 'type';
            }

            public function findClass(string $id): string
            {
                // Or do a DB lookup or whatever.
                return match ($id) {
                    'string' => StringItem::class,
                    'email' => EmailItem::class,
                    'LinkItem' => LinkItem::class,
                    'text' => TextItem::class,
                };
            }

            public function findIdentifier(string $class): string
            {
                return match ($class) {
                    StringItem::class => 'string',
                    EmailItem::class => 'email',
                    LinkItem::class => 'LinkItem',
                    TextItem::class => 'text',
                };
            }
        };

        $s = new SerdeCommon(formatters: $this->formatters, typeMaps: [
            Records\Drupal\Field::class => $typeMap,
        ]);

        $data = new Node('A node', 3, false, false);
        $data->fields[] = new FieldItemList('en', [
            new StringItem('foo'),
            new StringItem('bar'),
        ]);
        $data->fields[] = new FieldItemList('en', [
            new EmailItem('me@example.com'),
            new EmailItem('you@example.com'),
        ]);
        $data->fields[] = new FieldItemList('en', [
            new TextItem('Stuff here', 'plain'),
            new TextItem('More things', 'raw_html'),
        ]);
        $data->fields[] = new FieldItemList('en', [
            new LinkItem(uri: 'https://typo3.com', title: 'TYPO3'),
            new LinkItem(uri: 'https://google.com', title: 'Big Evil'),
        ]);

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: Node::class);

        self::assertEquals($data, $result);
    }

    #[Test, Group('flattening'), Group('typemap')]
    public function flatten_and_map_objects(): void
    {
        foreach ($this->formatters as $formatter) {
            if (($formatter->format() === $this->format) && !$formatter instanceof SupportsCollecting) {
                $this->markTestSkipped('Skipping flattening tests on non-flattening formatters');
            }
        }

        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new Wrapper(
            one: new ThingOneA(
                first: 'one',
                second: 'two',
            ),
            two: new ThingTwoC(
                fifth: 'five',
                sixth: 'six',
            ),
            other: [
                'more' => 'data',
                'goes' => 'here',
            ]
        );

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: Wrapper::class);

        self::assertEquals($data, $result);
    }

    #[Test]
    public function invalid_type_field(): void
    {
        $this->expectException(FieldTypeIncompatible::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        $a = new InvalidFieldType();

        // This should throw an exception when the loop is detected.
        $serialized = $s->serialize($a, $this->format);
    }

    #[Test]
    public function array_imploding(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new ImplodingArrays(
            seq: ['a', 'b', 'c'],
            dict: ['a' => 'A', 'b' => 'B', 'c' => 'C']
        );

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: ImplodingArrays::class);

        self::assertEquals($data, $result);
    }

    #[Test, Group('flattening'), Group('typemap')]
    public function flat_map_nested(): void
    {
        foreach ($this->formatters as $formatter) {
            if (($formatter->format() === $this->format) && !$formatter instanceof SupportsCollecting) {
                $this->markTestSkipped('Skipping flattening tests on non-flattening formatters');
            }
        }

        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new HostObject(
            nested: new NestedA(
                name: 'Bob',
                item: new Item(1, 2),
                items: [
                    new Item(3, 4),
                    new Item(5, 6),
                ],
            ),
            list: [
                new Item(7, 8),
                new Item(9, 10),
            ]
        );

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: HostObject::class);

        self::assertEquals($data, $result);
    }

    #[Test]
    public function post_deserialize_callback(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new CallbackHost(first: 'Larry', last: 'Garfield');

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        /** @var CallbackHost $result */
        $result = $s->deserialize($serialized, from: $this->format, to: CallbackHost::class);

        self::assertEquals($data, $result);

        self::assertEquals('Larry Garfield', $result->fullName);
    }

    #[Test, Group('typemap')]
    public function mapped_arrays(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new ShapeList(
            shapeSeq: [
                new Circle(new TwoDPoint(3, 4), 5),
                new Rectangle(new TwoDPoint(1, 2), new TwoDPoint(3, 4)),
            ],
            shapeDict: [
                'one' => new Rectangle(new TwoDPoint(5, 6), new TwoDPoint(7, 8)),
                'two' => new Circle(new TwoDPoint(9, 8), 7),
            ],
        );

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: ShapeList::class);

        self::assertEquals($data, $result);
    }

    #[Test, Group('typemap')]
    public function root_typemap(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new Rectangle(new TwoDPoint(1, 2), new TwoDPoint(3, 4));

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: Shape::class);

        self::assertEquals($data, $result);
    }

    #[Test]
    public function field_aliases(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $serialized = $this->aliasedData;

        $result = $s->deserialize($serialized, from: $this->format, to: AliasedFields::class);

        $expected = new AliasedFields(
            one: 1,
            two: 'dos',
            point: new Point(1, 2, 3),
        );

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function dictionary_key_int_in_string_throws_in_deserialize(): void
    {
        $this->expectException(InvalidArrayKeyType::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        $result = $s->deserialize($this->invalidDictIntKey, $this->format, DictionaryKeyTypes::class);
    }

    #[Test]
    public function dictionary_key_string_in_int_throws_on_serialize(): void
    {
        $this->expectException(InvalidArrayKeyType::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new DictionaryKeyTypes(
            stringKey: ['a' => 'A', 2 => 'B'],
            // The 'd' key here is invalid and won't serialize.
            intKey: [5 => 'C', 'd' => 'D'],
        );

        $serialized = $s->serialize($data, $this->format);
    }

    #[Test]
    public function datetime_fields_support_custom_output_format(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $timeString = '4 July 2022 14:22:22';
        $zone = new \DateTimeZone('America/New_York');

        $stamp = new \DateTime($timeString, $zone);
        $stampImmutable = new \DateTimeImmutable($timeString, $zone);
        $data = new DateTimeExample(
            default: $stamp,
            immutableDefault: $stampImmutable,
            ymd: $stamp,
            immutableYmd: $stampImmutable,
            forceToUtc: $stamp,
            forceToChicago: $stampImmutable,
        );

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        // Because some of the exported formats involve data loss,
        // we don't actually expect the exact same thing back.
        $expected = new DateTimeExample(
            default: $stamp,
            immutableDefault: $stampImmutable,
            // Because the timezone information is not serialized, it comes back
            // with none, and thus becomes UTC. This is expected.
            ymd: new \DateTime('4 July 2022 0:0:0', new \DateTimeZone('UTC')),
            immutableYmd: new \DateTimeImmutable('4 July 2022 0:0:0', new \DateTimeZone('UTC')),
            forceToUtc: $stamp,
            forceToChicago: $stampImmutable,
        );

        $result = $s->deserialize($serialized, from: $this->format, to: DateTimeExample::class);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function unixtime_fields_in_range_are_supported(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $timeString = '@1656958942.123456';

        $stamp = new \DateTime($timeString);
        $stampImmutable = new \DateTimeImmutable($timeString);
        $data = new UnixTimeExample(
            seconds: $stamp,
            milliseconds: $stampImmutable,
            microseconds: $stampImmutable
        );

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        // Because some of the exported formats involve data loss,
        // we don't actually expect the exact same thing back.
        $expected = new UnixTimeExample(
            seconds: new \DateTime('@1656958942'),
            milliseconds: new \DateTimeImmutable('@1656958942.123'),
            microseconds: new \DateTimeImmutable('@1656958942.123456')
        );

        $result = $s->deserialize($serialized, from: $this->format, to: $data::class);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function unixtime_fields_out_of_range_throw(): void
    {
        $this->expectException(UnixTimestampOutOfRange::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        // Determined experimentally to be one second larger than the allowed range.
        $timeString = '@9223372036856';

        $stamp = new \DateTime($timeString);
        $stampImmutable = new \DateTimeImmutable($timeString);
        $data = new UnixTimeExample(
            seconds: $stamp,
            milliseconds: $stampImmutable,
            microseconds: $stampImmutable
        );

        $this->expectExceptionObject(
            UnixTimestampOutOfRange::create($stampImmutable, UnixTimeResolution::Microseconds)
        );

        $s->serialize($data, $this->format);
    }

    #[Test]
    public function dictionary_key_string_in_int_throws_on_deserialize(): void
    {
        $this->expectException(InvalidArrayKeyType::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        $result = $s->deserialize($this->invalidDictStringKey, $this->format, DictionaryKeyTypes::class);
    }

    #[Test]
    public function generator_property_is_run_out(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $intSeq = static function (): iterable {
            yield from [1, 2, 3];
        };

        $intDict = static function (): iterable {
            yield from ['a' => 1, 'b' => 2, 'c' => 3];
        };

        $pointSeq = static function (): iterable {
            yield new Point(1, 2, 3);
            yield new Point(4, 5, 6);
            yield new Point(7, 2, 9);
        };

        $pointDict = static function (): iterable {
            yield 'A' => new Point(1, 2, 3);
            yield 'B' => new Point(4, 5, 6);
            yield 'C' => new Point(7, 2, 9);
        };

        $data = new Iterables(
            lazyInts: $intSeq(),
            lazyIntDict: $intDict(),
            lazyPoints: $pointSeq(),
            lazyPointsDict: $pointDict(),
        );

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);


        // Deserialization is always to an array, so we
        // need a separate expected object.
        $expected = new Iterables(
            lazyInts: iterator_to_array($intSeq()),
            lazyIntDict: iterator_to_array($intDict()),
            lazyPoints: iterator_to_array($pointSeq()),
            lazyPointsDict: iterator_to_array($pointDict()),
        );

        $result = $s->deserialize($serialized, from: $this->format, to: Iterables::class);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function traversable_object_not_iterated(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $intSeq = new TraversableInts(4);

        $pointSeq = new TraversablePoints(3, new Point(1, 1, 1));

        $data = new Traversables(
            lazyInts: $intSeq,
            lazyPoints: $pointSeq,
        );

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: Traversables::class);

        self::assertEquals($data, $result);
    }

    #[Test]
    public function missing_required_value_throws(): void
    {
        $this->expectException(MissingRequiredValueWhenDeserializing::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        $result = $s->deserialize($this->emptyData, $this->format, RequiresFieldValues::class);
    }

    #[Test]
    public function missing_required_value_with_default_does_not_throw(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        /** @var RequiresFieldValues $result */
        $result = $s->deserialize($this->missingOptionalData, $this->format, RequiresFieldValues::class);

        self::assertEquals('A', $result->a);
        // This isn't in the incoming data, and is required, but has a default so it's fine.
        self::assertEquals('B', $result->b);
    }

    #[Test]
    public function missing_required_value_for_class_throws(): void
    {
        $this->expectException(MissingRequiredValueWhenDeserializing::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        $result = $s->deserialize($this->emptyData, $this->format, RequiresFieldValuesClass::class);
    }

    #[Test]
    public function missing_required_value_for_class_with_default_does_not_throw(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        /** @var RequiresFieldValuesClass $result */
        $result = $s->deserialize($this->missingOptionalData, $this->format, RequiresFieldValuesClass::class);

        self::assertEquals('A', $result->a);
        // This isn't in the incoming data, and is required, but has a default so it's fine.
        self::assertEquals('B', $result->b);
    }

    #[Test]
    public function missing_required_value_with_attribute_default_uses_default(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        /** @var ExplicitDefaults $result */
        $result = $s->deserialize($this->emptyData, $this->format, ExplicitDefaults::class);

        self::assertEquals(42, $result->bar);
        // This isn't in the incoming data, and is required, but has a default so it's fine.
        self::assertEquals(null, $result->name);
    }

    /**
     * @param array<string|null> $scopes
     */
    #[Test, DataProvider('scopes_examples')]
    public function scopes(object $data, array $scopes, MultipleScopes|MultipleScopesDefaultTrue $expected): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $serialized = $s->serialize($data, $this->format, scopes: $scopes);

        // Note that we're deserializing into no-scope here, so that we can get the default
        // values for the missing properties.
        $result = $s->deserialize($serialized, from: $this->format, to: get_class($data));
        self::assertEquals($expected, $result);
    }

    public static function scopes_examples(): iterable
    {
        yield 'default false; default scope' => [
            'data' => new MultipleScopes(a: 'A', b: 'B', c: 'C', d: 'D', e: 'E'),
            'scopes' => [],
            'expected' => new MultipleScopes(a: 'A', b: 'B', c: 'C', d: 'D', e: 'E'),
        ];
        yield 'default false; scope one' => [
            'data' => new MultipleScopes(a: 'A', b: 'B', c: 'C', d: 'D', e: 'E'),
            'scopes' => ['one'],
            'expected' => new MultipleScopes(a: 'A', b: 'B', c: '', d: ''),
        ];
        yield 'default false; scope two' => [
            'data' => new MultipleScopes(a: 'A', b: 'B', c: 'C', d: 'D', e: 'E'),
            'scopes' => ['two'],
            'expected' => new MultipleScopes(a: 'A', b: '', c: 'C', d: ''),
        ];
        yield 'default false; scope one, two' => [
            'data' => new MultipleScopes(a: 'A', b: 'B', c: 'C', d: 'D', e: 'E'),
            'scopes' => ['one', 'two'],
            'expected' => new MultipleScopes(a: 'A', b: 'B', c: 'C', d: ''),
        ];

        yield 'default true; default scope' => [
            'data' => new MultipleScopesDefaultTrue(a: 'A', b: 'B', c: 'C', d: 'D', e: 'E'),
            'scopes' => [],
            'expected' => new MultipleScopesDefaultTrue(a: 'A', b: 'B', c: 'C', d: 'D', e: 'E'),
        ];
        yield 'default true; scope one' => [
            'data' => new MultipleScopesDefaultTrue(a: 'A', b: 'B', c: 'C', d: 'D', e: 'E'),
            'scopes' => ['one'],
            'expected' => new MultipleScopesDefaultTrue(a: 'A', b: 'B', c: 'C', d: '', e: 'E'),
        ];
        yield 'default true; scope two' => [
            'data' => new MultipleScopesDefaultTrue(a: 'A', b: 'B', c: 'C', d: 'D', e: 'E'),
            'scopes' => ['two'],
            'expected' => new MultipleScopesDefaultTrue(a: 'A', b: '', c: 'C', d: '', e: 'E'),
        ];
        yield 'default true; scope one, two' => [
            'data' => new MultipleScopesDefaultTrue(a: 'A', b: 'B', c: 'C', d: 'D', e: 'E'),
            'scopes' => ['one', 'two'],
            'expected' => new MultipleScopesDefaultTrue(a: 'A', b: 'B', c: 'C', d: '', e: 'E'),
        ];
    }

    #[Test]
    public function nullable_null_properties_are_allowed(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new NullProps();

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        /** @var NullProps $result */
        $result = $s->deserialize($serialized, from: $this->format, to: $data::class);

        self::assertNull($result->int);
        self::assertNull($result->float);
        self::assertNull($result->string);
        self::assertNull($result->array);
        self::assertNull($result->object);

        self::assertEquals($data, $result);
    }

    #[Test, Group('flattening')]
    public function nullable_properties_flattened(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new FlattenedNullableMain();

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        /** @var FlattenedNullableMain $result */
        $result = $s->deserialize($serialized, from: $this->format, to: $data::class);

        self::assertEquals($data, $result);
    }

    #[Test]
    public function lists_in_weak_mode_coerce_elements(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        /** @var WeakLists $result */
        $result = $s->deserialize($this->weakModeLists, $this->format, WeakLists::class);

        self::assertIsArray($result->seq);
        self::assertIsArray($result->dict);

        foreach ([...$result->seq, ...$result->dict] as $val) {
            self::assertIsInt($val);
        }
    }

    #[Test]
    public function non_sequence_arrays_are_normalized_to_sequences(): void
    {
        // This test is wrong, because serializing is going to force both values to sequences.
        // So we need to test deserialization separately.  Maybe go deeper for class-specific
        // unit tests.
        $s = new SerdeCommon(formatters: $this->formatters);

        $data = new SequenceOfStrings(['a' => 'A', 'b' => 'B'], ['a' => 'A', 'b' => 'B']);

        $serialized = $s->serialize($data, $this->format);

        // This will do the actual validation.
        $this->validateSerialized($serialized, __FUNCTION__);
//
//        /** @var SequenceOfStrings $result */
//        $result = $s->deserialize($serialized, from: $this->format, to: $data::class);
//

    }

    #[Test]
    public function non_sequence_arrays_in_strict_mode_throw(): void
    {
        $this->expectException(TypeMismatch::class);

        $s = new SerdeCommon(formatters: $this->formatters);

        $result = $s->deserialize($this->dictsInSequenceShouldFail, from: $this->format, to: SequenceOfStrings::class);
    }

    #[Test]
    public function non_sequence_arrays_in_weak_mode_are_coerced(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $result = $s->deserialize($this->dictsInSequenceShouldPass, from: $this->format, to: SequenceOfStrings::class);

        self::assertTrue(array_is_list($result->strict), 'The strict array is not a proper sequence');
        self::assertTrue(array_is_list($result->nonstrict), 'The nonstrict array is not a proper sequence');
        self::assertEquals('A', $result->strict[0]);
        self::assertEquals('B', $result->strict[1]);
        self::assertEquals('A', $result->nonstrict[0]);
        self::assertEquals('B', $result->nonstrict[1]);
    }

    #[Test]
    public function arrays_with_invalid_scalar_values(): void
    {
        $s = new SerdeCommon(formatters: $this->formatters);

        $this->expectException(TypeMismatch::class);

        // This should serialize fine, but then refuse to deserialize because
        // of the floats in the int section.
        $data = new ScalarArrays(
            ints: [1.1, 2.2, 3],
            floats: [3.14, 2.7],
            stringMap: ['a' => 'A'],
            arrayMap: ['a' => [1, 2, 3]],
        );

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);


        $result = $s->deserialize($serialized, from: $this->format, to: $data::class);
    }

    #[Test]
    public function transitive_type_field_is_recognized(): void
    {
        $exporter = new class () extends ObjectExporter {
            public bool $wasCalled = false;

            public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
            {
                $this->wasCalled = true;
                return parent::exportValue($serializer, $field, $value, $runningValue);
            }

            public function canExport(Field $field, mixed $value, string $format): bool
            {
                return $field->typeField instanceof TransitiveTypeField;
            }
        };
        $importer = new class() extends ObjectImporter {
            public bool $wasCalled = false;

            public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
            {
                $this->wasCalled = true;
                return parent::importValue($deserializer, $field, $source);
            }

            public function canImport(Field $field, string $format): bool
            {
                return $field->typeField instanceof TransitiveTypeField;
            }
        };

        $s = new SerdeCommon(handlers: [$exporter, $importer], formatters: $this->formatters);

        $data = new ClassWithPropertyWithTransitiveTypeField(new TransitiveField('Beep'));

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: $data::class);

        self::assertEquals($data, $result);
        self::assertTrue($exporter->wasCalled);
        self::assertTrue($importer->wasCalled);
    }

    #[Test]
    public function objects_can_be_reduced_to_primitive(): void
    {
        $exporter = new class () implements Exporter {
            public bool $wasCalled = false;

            public function exportValue(Serializer $serializer, Field $field, mixed $value, mixed $runningValue): mixed
            {
                $this->wasCalled = true;
                return $serializer->formatter->serializeInt($runningValue, $field, $value->id);
            }

            public function canExport(Field $field, mixed $value, string $format): bool
            {
                return $field->typeField instanceof ReducingTransitiveTypeField;
            }
        };
        $importer = new class() extends ObjectImporter {
            public bool $wasCalled = false;

            public function importValue(Deserializer $deserializer, Field $field, mixed $source): mixed
            {
                $this->wasCalled = true;
                $id = $deserializer->deformatter->deserializeInt($source, $field);

                return match ($id) {
                    1 => new ReducibleClass(1, 'One'),
                    2 => new ReducibleClass(2, 'Two'),
                    default => throw new \Exception('Record not found'),
                };
            }

            public function canImport(Field $field, string $format): bool
            {
                return $field->typeField instanceof ReducingTransitiveTypeField;
            }
        };

        $s = new SerdeCommon(handlers: [$exporter, $importer], formatters: $this->formatters);

        $data = new ClassWithReducibleProperty(new ReducibleClass(1, 'One'));

        $serialized = $s->serialize($data, $this->format);

        $this->validateSerialized($serialized, __FUNCTION__);

        $result = $s->deserialize($serialized, from: $this->format, to: $data::class);

        self::assertEquals($data, $result);
        self::assertTrue($exporter->wasCalled);
        self::assertTrue($importer->wasCalled);
    }
}
