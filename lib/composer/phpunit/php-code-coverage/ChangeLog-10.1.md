# ChangeLog

All notable changes are documented in this file using the [Keep a CHANGELOG](http://keepachangelog.com/) principles.

## [10.1.16] - 2024-08-22

### Changed

* Updated dependencies (so that users that install using Composer's `--prefer-lowest` CLI option also get recent versions)

## [10.1.15] - 2024-06-29

### Fixed

* [#967](https://github.com/sebastianbergmann/php-code-coverage/issues/967): Identification of executable lines for `match` expressions does not work correctly

## [10.1.14] - 2024-03-12

### Fixed

* [#1033](https://github.com/sebastianbergmann/php-code-coverage/issues/1033): `@codeCoverageIgnore` annotation does not work on `enum`

## [10.1.13] - 2024-03-09

### Changed

* [#1032](https://github.com/sebastianbergmann/php-code-coverage/pull/1032): Pad lines in code coverage report only when colors are shown

## [10.1.12] - 2024-03-02

### Changed

* Do not use implicitly nullable parameters

## [10.1.11] - 2023-12-21

### Changed

* This component is now compatible with `nikic/php-parser` 5.0

## [10.1.10] - 2023-12-11

### Fixed

* [#1023](https://github.com/sebastianbergmann/php-code-coverage/issues/1023): Branch Coverage and Path Coverage are not correctly reported for traits

## [10.1.9] - 2023-11-23

### Fixed

* [#1020](https://github.com/sebastianbergmann/php-code-coverage/issues/1020): Single line method is ignored

## [10.1.8] - 2023-11-15

### Fixed

* [#1018](https://github.com/sebastianbergmann/php-code-coverage/issues/1018): Interface methods are not ignored when their signature is split over multiple lines

## [10.1.7] - 2023-10-04

### Fixed

* [#1014](https://github.com/sebastianbergmann/php-code-coverage/issues/1014): Incorrect statement count in coverage report for constructor property promotion

## [10.1.6] - 2023-09-19

### Fixed

* [#1012](https://github.com/sebastianbergmann/php-code-coverage/issues/1012): Cobertura report pulls functions from report scope, not the individual element

## [10.1.5] - 2023-09-12

### Changed

* [#1011](https://github.com/sebastianbergmann/php-code-coverage/pull/1011): Avoid serialization of cache data in PHP report

## [10.1.4] - 2023-08-31

### Fixed

* Exceptions of type `SebastianBergmann\Template\Exception` are now properly handled

## [10.1.3] - 2023-07-26

### Changed

* The result of `CodeCoverage::getReport()` is now cached

### Fixed

* Static analysis cache keys do not include configuration settings that affect source code parsing
* The Clover, Cobertura, Crap4j, and PHP report writers no longer create a `php:` directory when they should write to `php://stdout`, for instance

## [10.1.2] - 2023-05-22

### Fixed

* [#998](https://github.com/sebastianbergmann/php-code-coverage/pull/998): Group Use Declarations are not handled properly

## [10.1.1] - 2023-04-17

### Fixed

* [#994](https://github.com/sebastianbergmann/php-code-coverage/issues/994): Argument `$linesToBeIgnored` of `CodeCoverage::stop()` has no effect for files that are not executed at all

## [10.1.0] - 2023-04-13

### Added

* [#982](https://github.com/sebastianbergmann/php-code-coverage/issues/982): Add option to ignore lines from code coverage

### Deprecated

* The `SebastianBergmann\CodeCoverage\Filter::includeDirectory()`, `SebastianBergmann\CodeCoverage\Filter::excludeDirectory()`, and `SebastianBergmann\CodeCoverage\Filter::excludeFile()` methods are now deprecated

[10.1.16]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.15...10.1.16
[10.1.15]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.14...10.1.15
[10.1.14]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.13...10.1.14
[10.1.13]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.12...10.1.13
[10.1.12]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.11...10.1.12
[10.1.11]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.10...10.1.11
[10.1.10]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.9...10.1.10
[10.1.9]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.8...10.1.9
[10.1.8]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.7...10.1.8
[10.1.7]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.6...10.1.7
[10.1.6]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.5...10.1.6
[10.1.5]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.4...10.1.5
[10.1.4]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.3...10.1.4
[10.1.3]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.2...10.1.3
[10.1.2]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.1...10.1.2
[10.1.1]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.1.0...10.1.1
[10.1.0]: https://github.com/sebastianbergmann/php-code-coverage/compare/10.0.2...10.1.0
