# Change Log

All notable changes to `sebastianbergmann/object-enumerator` are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [5.0.0] - 2023-02-03

### Removed

* This component is no longer supported on PHP 7.3, PHP 7.4 and PHP 8.0

## [4.0.4] - 2020-10-26

### Fixed

* `SebastianBergmann\ObjectEnumerator\Exception` now correctly extends `\Throwable`

## [4.0.3] - 2020-09-28

### Changed

* Changed PHP version constraint in `composer.json` from `^7.3 || ^8.0` to `>=7.3`

## [4.0.2] - 2020-06-26

### Added

* This component is now supported on PHP 8

## [4.0.1] - 2020-06-15

### Changed

* Tests etc. are now ignored for archive exports

## [4.0.0] - 2020-02-07

### Removed

* This component is no longer supported on PHP 7.0, PHP 7.1, and PHP 7.2

## [3.0.3] - 2017-08-03

### Changed

* Bumped required version of `sebastian/object-reflector`

## [3.0.2] - 2017-03-12

### Changed

* `sebastian/object-reflector` is now a dependency

## [3.0.1] - 2017-03-12

### Fixed

* Objects aggregated in inherited attributes are not enumerated

## [3.0.0] - 2017-03-03

### Removed

* This component is no longer supported on PHP 5.6

## [2.0.1] - 2017-02-18

### Fixed

* Fixed [#2](https://github.com/sebastianbergmann/phpunit/pull/2): Exceptions in `ReflectionProperty::getValue()` are not handled

## [2.0.0] - 2016-11-19

### Changed

* This component is now compatible with `sebastian/recursion-context: ~1.0.4`

## 1.0.0 - 2016-02-04

### Added

* Initial release

[5.0.0]: https://github.com/sebastianbergmann/object-enumerator/compare/4.0.4...5.0.0
[4.0.4]: https://github.com/sebastianbergmann/object-enumerator/compare/4.0.3...4.0.4
[4.0.3]: https://github.com/sebastianbergmann/object-enumerator/compare/4.0.2...4.0.3
[4.0.2]: https://github.com/sebastianbergmann/object-enumerator/compare/4.0.1...4.0.2
[4.0.1]: https://github.com/sebastianbergmann/object-enumerator/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/sebastianbergmann/object-enumerator/compare/3.0.3...4.0.0
[3.0.3]: https://github.com/sebastianbergmann/object-enumerator/compare/3.0.2...3.0.3
[3.0.2]: https://github.com/sebastianbergmann/object-enumerator/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/sebastianbergmann/object-enumerator/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/sebastianbergmann/object-enumerator/compare/2.0...3.0.0
[2.0.1]: https://github.com/sebastianbergmann/object-enumerator/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/sebastianbergmann/object-enumerator/compare/1.0...2.0.0

