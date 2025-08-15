# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [5.1.2] - 2024-03-02

### Changed

* Do not use implicitly nullable parameters

## [5.1.1] - 2023-09-24

### Changed

* [#52](https://github.com/sebastianbergmann/exporter/pull/52): Optimize export of large arrays and object graphs

## [5.1.0] - 2023-09-18

### Changed

* [#51](https://github.com/sebastianbergmann/exporter/pull/51): Export arrays using short array syntax

## [5.0.1] - 2023-09-08

### Fixed

* [#49](https://github.com/sebastianbergmann/exporter/issues/49): `Exporter::toArray()` changes `SplObjectStorage` index

## [5.0.0] - 2023-02-03

### Changed

* [#42](https://github.com/sebastianbergmann/exporter/pull/42): Improve export of enumerations

### Removed

* This component is no longer supported on PHP 7.3, PHP 7.4 and PHP 8.0

## [4.0.5] - 2022-09-14

### Fixed

* [#47](https://github.com/sebastianbergmann/exporter/pull/47): Fix `float` export precision

## [4.0.4] - 2021-11-11

### Changed

* [#37](https://github.com/sebastianbergmann/exporter/pull/37): Improve export of closed resources

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

## [3.1.5] - 2022-09-14

### Fixed

* [#47](https://github.com/sebastianbergmann/exporter/pull/47): Fix `float` export precision

## [3.1.4] - 2021-11-11

### Changed

* [#38](https://github.com/sebastianbergmann/exporter/pull/38): Improve export of closed resources

## [3.1.3] - 2020-11-30

### Changed

* Changed PHP version constraint in `composer.json` from `^7.0` to `>=7.0`

## [3.1.2] - 2019-09-14

### Fixed

* [#29](https://github.com/sebastianbergmann/exporter/pull/29): Second parameter for `str_repeat()` must be an integer

### Removed

* Remove HHVM-specific code that is no longer needed

[5.1.2]: https://github.com/sebastianbergmann/exporter/compare/5.1.1...5.1.2
[5.1.1]: https://github.com/sebastianbergmann/exporter/compare/5.1.0...5.1.1
[5.1.0]: https://github.com/sebastianbergmann/exporter/compare/5.0.1...5.1.0
[5.0.1]: https://github.com/sebastianbergmann/exporter/compare/5.0.0...5.0.1
[5.0.0]: https://github.com/sebastianbergmann/exporter/compare/4.0.5...5.0.0
[4.0.5]: https://github.com/sebastianbergmann/exporter/compare/4.0.4...4.0.5
[4.0.4]: https://github.com/sebastianbergmann/exporter/compare/4.0.3...4.0.4
[4.0.3]: https://github.com/sebastianbergmann/exporter/compare/4.0.2...4.0.3
[4.0.2]: https://github.com/sebastianbergmann/exporter/compare/4.0.1...4.0.2
[4.0.1]: https://github.com/sebastianbergmann/exporter/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/sebastianbergmann/exporter/compare/3.1.2...4.0.0
[3.1.5]: https://github.com/sebastianbergmann/exporter/compare/3.1.4...3.1.5
[3.1.4]: https://github.com/sebastianbergmann/exporter/compare/3.1.3...3.1.4
[3.1.3]: https://github.com/sebastianbergmann/exporter/compare/3.1.2...3.1.3
[3.1.2]: https://github.com/sebastianbergmann/exporter/compare/3.1.1...3.1.2
