# Serde

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

Serde (pronounced "seer-dee") is a fast, flexible, powerful, and easy to use serialization and deserialization library for PHP that supports a number of standard formats.  It draws inspiration from both Rust's Serde crate and Symfony Serializer, although it is not directly based on either.

At this time, Serde supports serializing PHP objects to and from PHP arrays, JSON, and YAML.  It also supports serializing to JSON via a stream.  Further support is planned, but by design can also be extended by anyone.

Serde is currently in late-beta.  It's possible there will be some small API changes still, but it should be mostly-stable and safe to use.

## Install

Via Composer

``` bash
$ composer require crell/serde
```

## Usage

Serde is designed to be both quick to start using and robust in more advanced cases.  In its most basic form, you can do the following:

```php
use Crell\Serde\SerdeCommon;

$serde = new SerdeCommon();

$object = new SomeClass();
// Populate $object somehow;

$jsonString = $serde->serialize($object, to: 'json');

$deserializedObject = $serde->deserialize($jsonString, from: 'json', to: SomeClass::class);
```

(The named arguments are optional, but recommended.)

Serde is highly configurable, but common cases are supported by just using the `SerdeCommon` class as provided.  For most basic cases, that is all you need.

## Key features

### Supported formats

Serde can serialize to:

* PHP arrays (`array`)
* JSON (`json`)
* Streaming JSON (`json-stream`)
* YAML (`yaml`)

Serde can deserialize from:

* PHP arrays (`array`)
* JSON (`json`)
* YAML (`yaml`)

(YAML support requires the [`Symfony/Yaml`](https://github.com/symfony/yaml) library.)  XML support is in progress.

### Robust object support

Serde automatically supports nested objects in properties of other objects, which will be handled recursively as long as there are no circular references.

Serde handles `public`, `private`, `protected`, and `readonly` properties, both reading and writing, with optional default values.

If you try to serialize or deserialize an object that implements PHP's [`__serialize()`](https://www.php.net/manual/en/language.oop5.magic.php#object.serialize) or [`__unserialize()`](https://www.php.net/manual/en/language.oop5.magic.php#object.unserialize) hooks, those will be respected.  (If you want to read/write from PHP's internal serialization format, just call `serialize()`/`unserialize()` directly.)

Serde also supports post-load callbacks that allow you to re-initialize derived information if necessary without storing it in the serialized format.

PHP objects can be mutated to and from a serialized format.  Nested objects can be flattened or collected, classes with common interfaces can be mapped to the appropriate object, and array values can be imploded into a string for serialization and exploded back into an array when reading.

## Configuration

Serde's behavior is driven almost entirely through attributes.  Any class may be serialized from or deserialized to as-is with no additional configuration, but there is a great deal of configuration that may be opted-in to.

Attribute handling is provided by [`Crell/AttributeUtils`](https://github.com/Crell/AttributeUtils).  It is worth looking into as well.

The main attribute is the `Crell\Serde\Attributes\Field` attribute, which may be placed on any object property.  (Static properties are ignored.)  All of its arguments are optional, as is the `Field` itself.  (That is, adding `#[Field]` with no arguments is the same as not specifying it at all.)  The meaning of the available arguments is listed below.

Although not required, it is strongly recommended that you always use named arguments with attributes.  The precise order of arguments is *not guaranteed*.

In the examples below, the `Field` is generally referenced directly.  However, you may also import the namespace and then use namespaced versions of the attributes, like so:

```php
use Crell\Serde\Attributes as Serde;

#[Serde\ClassSettings(includeFieldsByDefault: false)]
class Person
{
    #[Serde\Field(serializedName: 'callme')]
    protected string $name = 'Larry';
}
```

Which you do is mostly a matter of preference, although if you are mixing Serde attributes with attributes from other libraries then the namespaced approach is advisable.

There is also a `ClassSettings` attribute that may be placed on classes to be serialized.  At this time it has only one argument, `includeFieldsByDefault`, which defaults to `true`.  If set to false, a property with no `#[Field]` attribute will be ignored.  It is equivalent to setting `exclude: true` on all properties implicitly.

### `exclude` (bool, default false)

If set to `true`, Serde will ignore the property entirely on both serializing and deserializing.

### `serializedName` (string, default null)

If provided, this string will be used as the name of a property when serialized out to a format and when reading it back in.  for example:

```php
use Crell\Serde\Attributes\Field;

class Person
{
    #[Field(serializedName: 'callme')]
    protected string $name = 'Larry';
}
```

Round trips to/from:

```json
{
    "callme": "Larry"
}
```

### `renameWith` (RenamingStrategy, default null)

The `renameWith` key specifies a way to mangle the name of the property to produce a serializedName.  The most common examples here would be case folding, say if serializing to a format that uses a different convention than PHP does.

The value of `renameWith` can be any object that implements the [`RenamingStrategy`](src/Renaming/RenamingStrategy.php) interface.  The most common versions are already provided via the `Cases` enum and `Prefix` class, but you are free to provide your own.

The `Cases` enum implements `RenamingStrategy` and provides a series of instances (cases) for common renaming.  For example:

```php
use Crell\Serde\Attributes\Field;
use Crell\Serde\Renaming\Cases;

class Person
{
    #[Field(renameWith: Cases::snake_case)]
    public string $firstName = 'Larry';

    #[Field(renameWith: Cases::CamelCase)]
    public string $lastName = 'Garfield';
}
```

Serializes to/from:

```json
{
    "first_name": "Larry",
    "LastName": "Garfield"
}
```

Available cases are:

* `Cases::UPPERCASE`
* `Cases::lowercase`
* `Cases::snake_case`
* `Cases::kebab_case` (renders with dashes, not underscores)
* `Cases::CamelCase`
* `Cases::lowerCamelCase`

The `Prefix` class attaches a prefix to values when serialized, but otherwise leaves the property name intact.

```php
use Crell\Serde\Attributes\Field;
use Crell\Serde\Renaming\Prefix;

class MailConfig
{
    #[Field(renameWith: new Prefix('mail_')]
    protected string $host = 'smtp.example.com';

    #[Field(renameWith: new Prefix('mail_')]
    protected int $port = 25;

    #[Field(renameWith: new Prefix('mail_')]
    protected string $user = 'me';

    #[Field(renameWith: new Prefix('mail_')]
    protected string $password = 'sssh';
}
```

Serializes to/from:

```json
{
    "mail_host": "smtp.example.com",
    "mail_port": 25,
    "mail_user": "me",
    "mail_password": "sssh"
}
```

If both `serializedName` and `renameWith` are specified, `serializedName` will be used and `renameWith` ignored.

### `alias` (array, default `[]`)

When deserializing (only), if the expected serialized name is not found in the incoming data, these additional property names will be examined to see if the value can be found.  If so, the value will be read from that key in the incoming data.  If not, it will behave the same as if the value was simply not found in the first place.

```php
use Crell\Serde\Attributes\Field;

class Person
{
    #[Field(alias: ['layout', 'design'])]
    protected string $format = '';
}
```

All three of the following JSON strings would be read into an identical object:

```json
{
    "format": "3-column-layout"
}
```

```json
{
    "layout": "3-column-layout"
}
```

```json
{
    "design": "3-column-layout"
}
```

This is mainly useful when an API key has changed, and legacy incoming data may still have an old key name.

### `useDefault` (bool, default true)

This key only applies on deserialization.  If a property of a class is not found in the incoming data, and this property is true, then a default value will be assigned instead.  If false, the value will be skipped entirely.  Whether the deserialized object is now in an invalid state depends on the object.

The default value to use is derived from a number of different locations.  The priority order of defaults is:

1. The value provided by the `default` argument to the `Field` attribute.
2. The default value provided by the code, as reported by Reflection.
3. The default value of an identically named constructor argument, if any.

So for example, the following class:

```php
use Crell\Serde\Attributes\Field;

class Person
{
    #[Field(default: 'Hidden')]
    public string $location;

    #Field[(useDefault: false)]
    public int $age;

    public function __construct(
        public string $name = 'Anonymous',
    ) {}
}
```

if deserialized from an empty source (such as `{}` in JSON), will result in an object with `location` set to `Hidden`, `name` set to `Anonymous`, and `age` still unintialized.

### `default` (mixed, default null)

This key only applies on deserialization.  If specified, then if a value is missing in the incoming data being deserialized this value will be used instead, regardless of what the default in the source code itself is.

### `strict` (bool, default true)

This key only applies on deserialization.  If set to `true`, a type mismatch in the incoming data will be rejected and an exception thrown.  If `false`, a deformatter will attempt to cast an incoming value according to PHP's normal casting rules.  That means, for example, `"1"` is a valid value for an integer property if `strict` is false, but will throw an exception if set to `true`.

The exact handling of this setting may vary slightly depending on the incoming format, as some formats handle their own types differently.  (For instance, everything is a string in XML.)

### `flatten` (bool, default false)

The `flatten` keyword can only be applied on an array or object property.  A property that is "flattened" will have all of its properties injected into the parent directly on serialization, and will have values from the parent "collected" into it on deserialization.

Multiple objects and arrays may be flattened (serialized), but on deserialization only the lexically last array property marked `flatten` will collect remaining keys.  Any number of objects may "collect" their properties, however.

As an example, consider pagination.  It may be very helpful to represent pagination information in PHP as an object property of a result set, but in the serialized JSON or XML you may want the extra object removed.

Given this set of classes:

```php
use Crell\Serde\Attributes as Serde;

class Results
{
    public function __construct(
        #[Serde\Field(flatten: true)]
        public Pagination $pagination,
        #[Serde\SequenceField(arrayType: Product::class)]
        public array $products,
    ) {}
}

class Pagination
{
    public function __construct(
        public int $total,
        public int $offset,
        public int $limit,
    ) {}
}

class Product
{
    public function __construct(
        public string $name,
        public float $price,
    ) {}
}
```

When serialized, the `$pagination` object will get "flattened," meaning its three properties will be included directly in the properties of `Results`.  Therefore, a JSON-serialized copy of this object may look like:

```json
{
    "total": 100,
    "offset": 20,
    "limit": 10,
    "products": [
        {
            "name": "Widget",
            "price": 9.99
        },
        {
            "name": "Gadget",
            "price": 4.99
        }
    ]
}
```

The extra "layer" of the `Pagination` object has been removed.  When deserializing, those extra properties will be "collected" back into a `Pagination` object.

Now consider this more complex example:

```php
use Crell\Serde\Attributes as Serde;

class DetailedResults
{
    public function __construct(
        #[Serde\Field(flatten: true)]
        public NestedPagination $pagination,
        #[Serde\Field(flatten: true)]
        public ProductType $type,
        #[Serde\SequenceField(arrayType: Product::class)]
        public array $products,
        #[Serde\Field(flatten: true)]
        public array $other = [],
    ) {}
}

class NestedPagination
{
    public function __construct(
        public int $total,
        public int $limit,
        #[Serde\Field(flatten: true)]
        public PaginationState $state,
    ) {}
}

class PaginationState
{
    public function __construct(
        public int $offset,
    ) {
    }
}

class ProductType
{
    public function __construct(
        public string $name = '',
        public string $category = '',
    ) {}
}
```

In this example, both `NestedPagination` and `PaginationState` will be flattened when serializing.  `NestedPagination` itself also has a field that should be flattened.  Both will flatten and collect cleanly, as long as none of them share a property name.

Additionally, there is an extra array property, `$other`. `$other` may contain whatever associative array is desired, and its values will also get flattened into the output.

When collecting, only the lexically last flattened array will get any data, and will get all properties not already accounted for by some other property.  For example, an instance of `DetailedResults` may serialize to JSON as:

```json
{
    "total": 100,
    "offset": 20,
    "limit": 10,
    "products": [
        {
            "name": "Widget",
            "price": 9.99
        },
        {
            "name": "Gadget",
            "price": 4.99
        }
    ],
    "foo": "beep",
    "bar": "boop"
}
```

In this case, the `$other` property has two keys, `foo` and `bar`, with values `beep` and `boop`, respectively.  The same JSON will deserialize back to the same object as before.

### Sequences and Dictionaries

In most languages, and many serialization formats, there is a difference between a sequential list of values (called variously an array, sequence, or list) and a map of arbitrary size of arbitrary values to other arbitrary values (called a dictionary or map).  PHP does not make a distinction, and shoves both data types into a single associative array variable type.

Sometimes that works out, but other times the distinction between the two greatly matters.  To support those cases, Serde allows you to flag an array property as either a `#[SequenceField]` or `#[DictionaryField]` (and it is recommended that you always do so).  Doing so ensures that the correct serialization pathway is used for the property, and also opens up a number of additional features.

#### `arrayType`

On both a `#[SequenceField]` and `#[DictionaryField]`, the `arrayType` argument lets you specify the class that all values in that structure are.  For example, a sequence of integers can easily be serialized to and deserialized from most formats without any additional help.  However, an ordered list of `Product` objects could be serialized, but there's no way to tell then how to deserialize that data back to `Product` objects rather than just a nested associative array (which would also be legal).  The `arrayType` argument solves that issue.

If `arrayType` is specified, then all values of that array are assumed to be of that type.  On deserialization, then, Serde will look for nested object-like structures (depending on the specific format), and convert those into the specified object type.

For example:

```php
use Crell\Serde\Attributes\SequenceField;

class Order
{
    public string $orderId;

    public int $userId;

    #[SequenceField(arrayType: Product::class)]
    public array $products;
}
```

In this case, the attribute tells Serde that `$products` is an indexed, sequential list of `Product` objects.  When serializing, that may be represented as an array of dictionaries (in JSON or YAML) or perhaps with some additional metadata in other formats.

When deserializing, the otherwise object-ignorant data will be upcast back to `Product` objects.

`arrayType` works the exact same way on a `DictionaryField`.

#### `keyType`

On `DictionaryField` only, it's possible to restrict the array to only allowing integer or string keys.  It has two legal values, `KeyType::Int` and `KeyType::String` (an enum).  If set to `KeyType::Int`, then deserialization will reject any arrays that have string keys, but will accept numeric strings.  If set to `KeyType::String`, then deserialiation will reject any arrays that have integer keys, including numeric strings.

(PHP auto-casts integer string array keys to actual integers, so there is no way to allow them in string-based dictionaries.)

If no value is set, then either key type will be accepted.

#### `implodeOn`

The `implodeOn` argument to `SequenceField`, if present, indicates that the value should be joined into a string serialization, using the provided value as glue.  For example:

```php
use Crell\Serde\Attributes\SequenceField;

class Order
{
    #[SequenceField(implodeOn: ',')]
    protected array $productIds = [5, 6, 7];
}
```

Will serialize in JSON to:

```json
{
    "productIds": "5,6,7"
}
```

On deserialization, that string will get automatically get exploded back into an array when placed into the object.

By default, on deserialization the individual values will be `trim()`ed to remove excess whitespace.  That can be disabled by setting the `trim` attribute argument to `false`.

#### `joinOn`

`DictionaryField`s also support imploding/exploding on serialization, but require two keys.  `implodeOn` specifies the string to use between distinct values.  `joinOn` specifies the string to use between the key and value.

For example:

```php
use Crell\Serde\Attributes\DictionaryField;

class Settings
{
    #[DictionaryField(implodeOn: ',', joinOn: '=')]
    protected array $dimensions = [
        'height' => 40,
        'width' => 20,
    ];
}
```

Will serialize/deserialize to this JSON:

```json
{
    "dimensions": "height=40,width=20"
}
```

As with `SequenceField`, values will automatically be `trim()`ed unless `trim: false` is specified in the attribute's argument list.

### TypeMaps

Type maps are a powerful feature of Serde that allows precise control over how objects with inheritance are serialized and deserialized.  Type Maps translate between the class of an object and some unique identifier that is included in the serialized data.

In the abstract, a Type Map is any object that implements the [`TypeMap`](src/TypeMap.php) interface.  TypeMaps may be provided as an attribute on a property, or on a class or interface, or provided to Serde when it is set up to allow for arbitrary maps.

Consider the following example, which will be used for the remaining explanations of Type Maps:

```php
use Crell\Serde\Attributes\SequenceField;

interface Product {}

interface Book extends Product {}

class PaperBook implements Book
{
    protected string $title;
    protected int $pages;
}

class DigitalBook implements Book
{
    protected string $title;
    protected int $bytes;
}

class Sale
{
    protected Book $book;

    protected float $discountRate;
}

class Order
{
    protected string $orderId;

    #[SequenceField(arrayType: Book::class)]
    protected array $products;
}
```

Both `Sale` and `Order` reference `Book`, but that value could be a `PaperBook`, `DigitalBook`, or any other class that implements `Book`.  Type Maps provide a way for Serde to tell which concrete type it is.

#### Class name maps

The simplest case of a class map is to include a `#[ClassNameTypeMap]` attribute on an object property.  For example, 

```php
use Crell\Serde\ClassNameTypeMap;

class Sale
{
    #[ClassNameTypeMap(key: 'type')]
    protected Book $book;

    protected float $discountRate;
}
```

Now when a `Sale` is serialized, an extra property will be included named `type` that contains the class name.  So a sale on a digital book would serialize like so:

```json
{
    "book": {
        "type": "Your\\App\\DigitalBook",
        "title": "Thinking Functionally in PHP",
        "bytes": 45000
    },
    "discountRate": 0.2
}
```

On deserialization, the "type" property will be read and used to determine that the remaining values should be used to construct a `DigitalBook` instance, specifically.

Class name maps have the advantage that they are very simple, and will work with any class that implements that interface, even those you haven't thought of yet.  The downside is that they put a PHP implementation detail (the class name) into the output, which may not be desireable.

#### Static Maps

Static maps allow you to provide a fixed map from classes to meaningful keys.

```php
use Crell\Serde\Attributes\StaticTypeMap;

class Sale
{
    #[StaticTypeMap(key: 'type', map: [
        'paper' => Book::class,
        'ebook' => DigitalBook::class,
    ])]
    protected Book $book;

    protected float $discountRate;
}
```

Now, if a `Sale` object is serialized it will look like this:

```json
{
    "book": {
        "type": "ebook",
        "title": "Thinking Functionally in PHP",
        "bytes": 45000
    },
    "discountRate": 0.2
}
```

Static maps have the advantage of simplicity and not polluting the output with PHP-specific implementation details.  The downside is that they are static: They can only handle the classes you know about at code time, and will throw an exception if they encounter any other class.

#### Type maps on collections

Type Maps may also be applied to array properties, either sequence or dictionary.  In that case, they will apply to all values in that collection.  For example:

```php
use Crell\Serde\Attributes as Serde;

class Order
{
    protected string $orderId;

    #[Serde\SequenceField(arrayType: Book::class)]
    #[Serde\StaticTypeMap(key: 'type', map: [
        'paper' => Book::class,
        'ebook' => DigitalBook::class,
    ])]
    protected array $books;
}
```

`$products` is an array of objects that implement `Book`, but could be either `PaperBook` or `DigitalBook`.  A serialized copy of this object may look like:

```json
{
    "orderId": "abc123",
    "products": [
        {
            "type": "ebook",
            "title": "Thinking Functionally in PHP",
            "bytes": 45000
        },
        {
            "type": "paper",
            "title": "Category Theory for Programmers",
            "pages": 335
        }
    ]
}
```

On deserialization, the `type` property will again be used to determine the class that the rest of the properties should be hydrated into.

#### Type mapped classes

In addition to putting a type map on a property, you may also place it on the class or interface that the property references.

```php
use Crell\Serde\Attributes\StaticTypeMap;

#[StaticTypeMap(key: 'type', map: [
    'paper' => Book::class,
    'ebook' => DigitalBook::class,
])]
interface Book {}
```

Now, that Type Map will apply to both `Sale::$book` and to `Order::$books` with no further work on our part.

Type Maps also inherit.  That means we can put a type map on `Product` instead if we wanted:

```php
use Crell\Serde\Attributes\StaticTypeMap;

#[StaticTypeMap(key: 'type', map: [
    'paper' => Book::class,
    'ebook' => DigitalBook::class,
    'toy' => Gadget::class,
])]
interface Product {}
```

And both `Sale` and `Order` will still serialize with the appropriate key.

#### Dynamic type maps

Type Maps may also be provided directly to the Serde object when it is created.  Any object that implements `TypeMap` may be used.  This is most useful when the list of possible classes is dynamic based on user configuration, database values, what plugins are installed in your application, etc.

```php
use Crell\Serde\TypeMap;

class ProductTypeMap implements TypeMap
{
    public function __construct(protected readonly Connection $db) {}

    public function keyField(): string
    {
        return 'type';
    }

    public function findClass(string $id): ?string
    {
        return $this->db->someLookup($id);
    }

    public function findIdentifier(string $class): ?string
    {
        return $this->db->someMappingLogic($class);
    }
}

$typeMap = new ProductTypeMap($dbConnection);

$serde = new SerdeCommon(typeMaps: [
    Your\App\Product::class => $typeMap,
]);

$json = $serde->serialize($aBook, to: 'json');
```

In practice, you would likely set that up via your Dependency Injection system.

Note that `ClassNameTypeMap` and `StaticTypeMap` may be injected as well, as can any other class that implements `TypeMap`.

#### Custom type maps

You may also write your own Type Maps as attributes.  The only requirements are:

1. The class implements the `TypeMap` interface.
2. The class is marked as an #[\Attribute].
3. The class is legal on *both* classes and properties. That is, `#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]`

### Scopes

Serde supports "scopes" for having different versions of an attribute recognized in different contexts.

Any attribute (`Field`, `TypeMap`, `SequenceField`, `DictionaryField`, `PostLoad`, etc.) may take a `scopes` argument, which accepts an array of strings.  If specified, that attribute is only valid if serializing or deserializing in that scope.  If no scoped attribute is specified, then the behavior will fall back to an unscoped attribute or an omitted attribute.

For example, given this class:

```php
class User
{
    private string $username;

    #[Field(exclude: true)]
    private string $password;

    #[Field(exclude: true)]
    #[Field(scope: 'admin')]
    private string $role;
}
```

If you serialize it like so:

```php
$json = $serde->serialize($user, 'json');
```

It will result in this JSON response:

```json
{
    "username": "Larry"
}
```

That's because, in an unscoped request, the first `Field` on `$role` is used, which excludes it from the output.  However, if you specify a scope:

```php
$json = $serde->serialize($user, 'json', scopes: ['admin']);
```

Then the `admin` version of `$role`'s `Field` will be used, which is not excluded, and get this result:

```json
{
    "username": "Larry",
    "role": "Developer"
}
```

When using scopes, it may be helpful to disable automatic property inclusion and require that each be specified explicitly.  For example:

```php
#[ClassSettings(includeFieldsByDefault: false)]
class Product
{
    #[Field]
    private int $id = 5;

    #[Field]
    #[Field(scopes: ['legacy'], serializedName: 'label')]
    private string $name = 'Fancy widget';

    #[Field(scopes: ['newsystem'])]
    private float $price = '9.99';

    #[Field(scopes: ['legacy'], serializedName: 'cost')]
    private float $legacyPrice = 9.99;

    #[Field(serializedName: 'desc')]
    private string $description = 'A fancy widget';

    private int $stock = 50;
}
```

If serialized with no scope specified, it will result in this:

```json
{
    "id": 5,
    "name": "Fancy widget",
    "desc": "A fancy widget"
}
```

As those are the only fields that are "in scope" when no scope is specified.

If serialized with the `legacy` scope:

```json
{
    "id": 5,
    "label": "Fancy widget",
    "cost": 9.99,
    "desc": "A fancy widget"
}
```

The scope-specific `Field` on `$name` gets used instead, which changes the serialized name. The `$legacyPrice` property is also included now, but renamed to "cost".

If serialized with the `newsystem` scope:

```json
{
    "id": 5,
    "label": "Fancy widget",
    "price": "9.99",
    "desc": "A fancy widget"
}
```

In this case, the `$name` property uses the unscoped version of `Field`, and so is not renamed.  The string-based `$price` is now in-scope, but the float-based `$legacyPrice` is not.  Note that in none of these cases is the current `$stock` included, as it has no attribute at all.

Finally, it's also possible to serialize multiple scopes simultaneously.  This is an OR operation, so any field marked for *any* specified scope will be included.

```php
$json = $serde->serialize($product, 'json', scopes: ['legacy', 'newsystem']);
```

```json
{
    "id": 5,
    "name": "Fancy widget",
    "price": "9.99",
    "cost": 9.99,
    "desc": "A fancy widget"
}
```

Note that since there is both an unscoped and a scoped version of the `Field` on `$name`, the scoped one wins and the property gets renamed.

If multiple attribute variants could apply for the specified scope, the lexically first in a scope will take precedence over later ones, and a scoped attribute will take precedence over an unscoped one.

Note that when deserializing, specifying a scope will exclude not only out-of-scope properties but their defaults as well.  That is, they will not be set, even to a default value, and so may be "uninitialized."  That is rarely desireable, so it may be preferable to deserialize without a scope, even if a value was serialized with a scope.  That will depend on your use case.

For more on scopes, see the [AttributeUtils](https://github.com/CrellAttributeUtils#Scopes) documentation.

## Extending Serde

Internally, Serde has five types of extensions that work in concert to produce a serialized or deserialized product.

* Type Maps, as discussed above, are optional and translate a class name to a lookup identifier and back.
* A [`Exporter`](src/PropertyHandler/Exporter.php) is responsible for pulling values off of an object, processing them if necessary, and then passing them on to a Formatter.  This is part of the Serialization pipeline.
* A [`Importer`](src/PropertyHandler/Importer.php) is responsible for using a Deformatter to extract data from incoming data and then translate it as necessary to be written to an object.  This is part of the Deserialization pipeline.
* A [`Formatter`](src/Formatter/Formatter.php) is responsible for writing to a specific output format, like JSON or YAML.  This is part of the Serialization pipeline.
* A [`Deformatter`](src/Formatter/Deformatter.php) is responsible for reading data off of an incoming format and passing it back to an `Importer`.  This is part of the Deserialization pipeline.

Collectively, `Importer` and `Exporter` instances are called "handlers."

In general, `Importer`s and `Exporter`s are *PHP-type specific*, while `Formatter`s and `Deformatter`s are *serialized-format specific*.  Custom Importers and Exporters can also declare themselves to be format-specific if they contain format-sensitive optimizations.

`Importer` and `Exporter` may be implemented on the same object, or not.  Similarly, `Formatter` and `Deformatter` may be implemented together or not.  That is up to whatever seems easiest for the particular implementation, and the provided extensions do a little of each depending on the use case.

The interfaces linked above provide more precise explanations of how to use them.  In most cases, you would only need to implement a Formatter or Deformatter to support a new format.  You would only need to implement an Importer or Exporter when dealing with a specific class that needs extra special handling for whatever reason, such as its serialized representation having little or no relationship with its object representation.

As an example, a few custom handlers are included to deal with common cases.

* [`DateTimeExporter`](src/PropertyHandler/DateTimeExporter.php): This object will translate `DateTime` and `DateTimeImmutable` objects to and from a serialized form as a string.  Specifically, it will use the `\DateTimeInterface::RFC3339_EXTENDED` format for the string when serializing.  The timestamp will then appear in the serialized output as a normal string.  When deserializing, it will accept any datetime format supported by `DateTime`'s constructor.
* [`DateTimeZoneExporter`](src/PropertyHandler/DateTimeZoneExporter.php): This object will translate `DateTimeZone` objects to and from a serialized form as a timezone string.  That is, `DateTimeZone('America/Chicago`)` will be represented in the format as the string `America/Chicago`.
* [`NativeSerializeExporter`](src/PropertyHandler/NativeSerializeExporter.php): This object will apply to any class that has a `__serialize()` method (when serializing) or `__unserialize()` method (when deserializing).  These PHP magic methods provide alternate representations of an object intended for use with PHP's native `serialize()` and `unserialize()` methods, but can also be used for any other format.  If `__serialize()` is defined, it will be invoked and whatever associative array it returns will be written to the selected format as a dictionary.  If `__unserialize()` is defined, this object will read a dictionary from the incoming data and then pass it to that method on a newly created object, which will then be responsible for populating the object as appropriate.  No further processing will be done in either direction.
* [`EnumOnArrayImporter`](src/PropertyHandler/EnumOnArrayImporter.php): Serde natively supports PHP Enums and can serialize them as ints or strings as appropriate.  However, in the special case of reading from a PHP array format this object will take over and support reading an Enum literal in the incoming data.  That allows, for example, a configuration array to include hand-inserted Enum values and still be cleanly imported into a typed, defined object.

## Dependency Injection configuration

Serde is designed to be usable "out of the box" without any additional setup.  However, when included in a larger system it is best to configure it properly via Dependency Injection.

There are three ways you can set up Serde.

1. The `SerdeCommon` class includes most available handlers and formatters out of the box, ready to go, although you can add additional ones via the constructor.
2. The `SerdeBasic` class has no pre-built configuration whatsoever; you will need to provide all Handlers, Formatters, or Type Maps you want yourself, in the order you want them applied.
3. You may also extend the `Serde` base class itself and create your own custom pre-made configuration, with just the Handlers or Formatters (provided or custom) that you want.

Both `SerdeCommon` and `SerdeBasic` take four arguments: The [`ClassAnalyzer`](https://github.com/Crell/AttributeUtils) to use, an array of Handlers, an array of Formatters, and an array of Type Maps.  If no analyzer is provided, Serde creates a memory-cached Analyzer by default so that it will always work.  However, in a DI configuration it is strongly recommended that you configure the Analyzer yourself, with appropriate caching, and inject that into Serde as a dependency to avoid duplicate Analyzers (and duplicate caches).  If you have multiple different Serde configurations in different services, it may also be beneficial to make all handlers and formatters services as well and explicitly inject them into `SerdeBasic` rather than relying on `SerdeCommon`.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email larry at garfieldtech dot com instead of using the issue tracker.

## Credits

- [Larry Garfield][link-author]
- [All Contributors][link-contributors]

Development of this library is sponsored by [TYPO3 GmbH](https://typo3.com/).

## License

The Lesser GPL version 3 or later. Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/Crell/Serde.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/License-LGPLv3-green.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/Crell/Serde.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/Crell/Serde
[link-scrutinizer]: https://scrutinizer-ci.com/g/Crell/Serde/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/Crell/Serde
[link-downloads]: https://packagist.org/packages/Crell/Serde
[link-author]: https://github.com/Crell
[link-contributors]: ../../contributors
