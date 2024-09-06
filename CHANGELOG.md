# Changelog

All notable changes to `Serde` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## 1.3.0 - DATE

### Added
- TOML support!  Provided by the [`vanodevium/toml`](https://github.com/vanodevium/toml) library (sold separately).  As with YAML, just require that library and Serde will pick it up and use it.  Slava Ukraine!
- Null values may now be excluded when serializing. See the `omitNullFields` and `omitIfNull` flags in the README.
- We now require AttributeUtils 1.2, which lets us use closures rather than method name strings for subAttribute callbacks. (Internal improvement.)
- When `strict` is false on a sequence or dictionary, numeric strings will get cast to an int or float as appropriate.  Previously the list values were processed in strict mode regardless of what the field was set to.

### Deprecated
- Nothing

### Fixed
- Greatly simplified and cleaned up the test suite.
- Added better escaping to JSON Stream Formatter to handle strings that contain quotes or other JSON-meaningful characters.
- Fixed handling of deserializing a nullable enum field.

### Removed
- Nothing

### Security
- Nothing

## 1.2.0 - 2024-06-04

This release includes a small *breaking change*.  The deformatter methods all now have nullable returns.  This is necessary to allow for deserializing values that are legitimately and permissibly null.  If you do not have any custom Importers, you should not be impacted.  If you do have a custom Importer, you *may* need to adjust your logic to account for the return value from the deformatter being null.

### Added
- `TypeField` is now `Transitive`, so you can implement a custom TypeField for a specific object, and it will apply anywhere it is used.
- `TypeField` is now `Inheritable`, too.
- There is a new `UnixTimeField` that can be applied to `DateTime*` properties.  As it says on the tin, it serializes to/from a Unix timestamp integer, in second, millisecond, or microsecond resolution.

### Deprecated
- Nothing

### Fixed
- Explicit null values in incoming data should now deserialize to null-valued properties, assuming the types permit.

### Removed
- Nothing

### Security
- Nothing

## 1.1.0 - 2024-01-20

The main change in this release is better support for flattening value objects.  See the additional section in the README for more details.

### Added
- A new `flattenPrefix` setting on flattened fields allows for having multiple properties of the same type that get flattened.  The prefix allows them to be differentiated.

### Deprecated
- Nothing

### Fixed
- `serializedName`/`renameWith` is now respected on fields in flattened objects.

### Removed
- Nothing

### Security
- Nothing

## 1.0.1 - 2023-11-1
- Forgot to include a changelog on 1.0.0, hence 1.0.1. Sigh.
- Include diagrams in the README.

## 1.0.0 - 2023-11-1

### Fixed
- Split up internal Enums for better type safety.
- Renamed internal Enums for clarity.
- Added a marker interface for all exceptions.

## 0.7.0 - 2023-10-21

### Added
- Null is now a legal value for a property to deserialize to.
- Null values will now be serialized as null, rather than omitted.
- BC BREAK: The return type of formatter methods have changed to support null as a legal value.
- `arrayType` on Sequences and Dictionaries can now enforce scalar types.
- Serde now uses PHPUnit 10.

### Fixed
- There was a bug that caused default values in attributes to be ignored in some cases.  That has been corrected.
- Flattened nullable objects previously got deserialized into empty objects.  Now they are left as null.
- Array-based sequences now support non-strict mode, in which they will accept non-sequence arrays but discard the keys.
- In the default SerdeCommon configuration, dictionaries are now checked first, meaning an un-attributed array will get interpreted as a dictionary, not a sequence.  This is to minimize data loss.  Explicitly specifying a sequence or dictionary attribute is strongly recommended in all cases.
- The sequence and dictionary exporters were eagerly processing lazy properties.  This has been corrected, and now a generator property will be serialized one element at a time.

## 0.6.0 - 2023-03-23

### Added
- Support for iterable properties, including generators.
- Support for serializing/deserializing from CSV files.
- Support for stream-serializing to a CSV format.
- Support for specifying a custom format and timezone when serializing DateTime fields.
- Support for making individual fields required when deserializing.
- Support for specifying at the class level that fields are required unless otherwise specified.

### Deprecated
- Nothing

### Fixed
- Nothing

### Removed
- Nothing

### Security
- Nothing

## 0.5.0 - 2022-07-22

### Added
- Dictionary fields can now be restricted to just string or just integer keys.
- TypeField definitions now have a validation method to vet values as supportable.

### Deprecated
- Nothing

### Fixed
- Dictionary fields now support integer keys by default.

### Removed
- Nothing

### Security
- Nothing
