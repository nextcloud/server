Changelog
=========

## UNRELEASED

## 1.9.1

## Fixed

* provisional support for PHP 8.0

## 1.9.0

* added better Psalm support for `all*` & `nullOr*` methods
  * These methods are now understood by Psalm through a mixin. You may need a newer version of Psalm in order to use this
* added `@psalm-pure` annotation to `Assert::notFalse()`
* added more `@psalm-assert` annotations where appropriate

## Changed

* the `all*` & `nullOr*` methods are now declared on an interface, instead of `@method` annotations.
This interface is linked to the `Assert` class with a `@mixin` annotation. Most IDE's have supported this
for a long time, and you should not lose any autocompletion capabilities. PHPStan has supported this since
version `0.12.20`. This package is marked incompatible (with a composer conflict) with phpstan version prior to that.
If you do not use PHPStan than this does not matter.

## 1.8.0

### Added

* added `Assert::notStartsWith()`
* added `Assert::notEndsWith()`
* added `Assert::inArray()`
* added `@psalm-pure` annotations to pure assertions

### Fixed

* Exception messages of comparisons between `DateTime(Immutable)` objects now display their date & time.
* Custom Exception messages for `Assert::count()` now use the values to render the exception message.

## 1.7.0 (2020-02-14)

### Added

* added `Assert::notFalse()`
* added `Assert::isAOf()`
* added `Assert::isAnyOf()`
* added `Assert::isNotA()`

## 1.6.0 (2019-11-24)

### Added

* added `Assert::validArrayKey()`
* added `Assert::isNonEmptyList()`
* added `Assert::isNonEmptyMap()`
* added `@throws InvalidArgumentException` annotations to all methods that throw.
* added `@psalm-assert` for the list type to the `isList` assertion.

### Fixed

* `ResourceBundle` & `SimpleXMLElement` now pass the `isCountable` assertions.
They are countable, without implementing the `Countable` interface.
* The doc block of `range` now has the proper variables.
* An empty array will now pass `isList` and `isMap`. As it is a valid form of both.
If a non-empty variant is needed, use `isNonEmptyList` or `isNonEmptyMap`.

### Changed

* Removed some `@psalm-assert` annotations, that were 'side effect' assertions See:
  * [#144](https://github.com/webmozart/assert/pull/144)
  * [#145](https://github.com/webmozart/assert/issues/145)
  * [#146](https://github.com/webmozart/assert/pull/146)
  * [#150](https://github.com/webmozart/assert/pull/150)
* If you use Psalm, the minimum version needed is `3.6.0`. Which is enforced through a composer conflict.
If you don't use Psalm, then this has no impact.

## 1.5.0 (2019-08-24)

### Added

* added `Assert::uniqueValues()`
* added `Assert::unicodeLetters()`
* added: `Assert::email()`
* added support for [Psalm](https://github.com/vimeo/psalm), by adding `@psalm-assert` annotations where appropriate.

### Fixed

* `Assert::endsWith()` would not give the correct result when dealing with a multibyte suffix.
* `Assert::length(), minLength, maxLength, lengthBetween` would not give the correct result when dealing with multibyte characters.

**NOTE**: These 2 changes may break your assertions if you relied on the fact that multibyte characters didn't behave correctly.

### Changed

* The names of some variables have been updated to better reflect what they are.
* All function calls are now in their FQN form, slightly increasing performance.
* Tests are now properly ran against HHVM-3.30 and PHP nightly.

### Deprecation

* deprecated `Assert::isTraversable()` in favor of `Assert::isIterable()`
  * This was already done in 1.3.0, but it was only done through a silenced `trigger_error`. It is now annotated as well.

## 1.4.0 (2018-12-25)

### Added

* added `Assert::ip()`
* added `Assert::ipv4()`
* added `Assert::ipv6()`
* added `Assert::notRegex()`
* added `Assert::interfaceExists()`
* added `Assert::isList()`
* added `Assert::isMap()`
* added polyfill for ctype

### Fixed

* Special case when comparing objects implementing `__toString()`

## 1.3.0 (2018-01-29)

### Added

* added `Assert::minCount()`
* added `Assert::maxCount()`
* added `Assert::countBetween()`
* added `Assert::isCountable()`
* added `Assert::notWhitespaceOnly()`
* added `Assert::natural()`
* added `Assert::notContains()`
* added `Assert::isArrayAccessible()`
* added `Assert::isInstanceOfAny()`
* added `Assert::isIterable()`

### Fixed

* `stringNotEmpty` will no longer report "0" is an empty string

### Deprecation

* deprecated `Assert::isTraversable()` in favor of `Assert::isIterable()`

## 1.2.0 (2016-11-23)

 * added `Assert::throws()`
 * added `Assert::count()`
 * added extension point `Assert::reportInvalidArgument()` for custom subclasses

## 1.1.0 (2016-08-09)

 * added `Assert::object()`
 * added `Assert::propertyExists()`
 * added `Assert::propertyNotExists()`
 * added `Assert::methodExists()`
 * added `Assert::methodNotExists()`
 * added `Assert::uuid()`

## 1.0.2 (2015-08-24)

 * integrated Style CI
 * add tests for minimum package dependencies on Travis CI

## 1.0.1 (2015-05-12)

 * added support for PHP 5.3.3

## 1.0.0 (2015-05-12)

 * first stable release

## 1.0.0-beta (2015-03-19)

 * first beta release
