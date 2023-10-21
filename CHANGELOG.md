# Changelog

All notable changes to `Serde` will be documented in this file.

Updates should follow the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## 0.7.0 - DATE

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
