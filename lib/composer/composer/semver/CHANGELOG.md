# Change Log

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

### [1.7.1] 2020-09-27

  * Fixed: accidental validation of broken constraints combining ^/~ and wildcards, and -dev suffix allowing weird cases
  * Fixed: normalization of beta0 and such which was dropping the 0

### [1.7.0] 2020-09-09

  * Added: support for `x || @dev`, not very useful but seen in the wild and failed to validate with 1.5.2/1.6.0
  * Added: support for `foobar-dev` being equal to `dev-foobar`, dev-foobar is the official way to write it but we need to support the other for BC and convenience

### [1.6.0] 2020-09-08

  * Added: support for constraints like `^2.x-dev` and `~2.x-dev`, not very useful but seen in the wild and failed to validate with 1.5.2
  * Fixed: invalid aliases will no longer throw, unless explicitly validated by Composer in the root package

### [1.5.2] 2020-09-08

  * Fixed: handling of some invalid -dev versions which were seen as valid
  * Fixed: some doctypes

### [1.5.1] 2020-01-13

  * Fixed: Parsing of aliased version was not validating the alias to be a valid version

### [1.5.0] 2019-03-19

  * Added: some support for date versions (e.g. 201903) in `~` operator
  * Fixed: support for stabilities in `~` operator was inconsistent

### [1.4.2] 2016-08-30

  * Fixed: collapsing of complex constraints lead to buggy constraints

### [1.4.1] 2016-06-02

  * Changed: branch-like requirements no longer strip build metadata - [composer/semver#38](https://github.com/composer/semver/pull/38).

### [1.4.0] 2016-03-30

  * Added: getters on MultiConstraint - [composer/semver#35](https://github.com/composer/semver/pull/35).

### [1.3.0] 2016-02-25

  * Fixed: stability parsing - [composer/composer#1234](https://github.com/composer/composer/issues/4889).
  * Changed: collapse contiguous constraints when possible.

### [1.2.0] 2015-11-10

  * Changed: allow multiple numerical identifiers in 'pre-release' version part.
  * Changed: add more 'v' prefix support.

### [1.1.0] 2015-11-03

  * Changed: dropped redundant `test` namespace.
  * Changed: minor adjustment in datetime parsing normalization.
  * Changed: `ConstraintInterface` relaxed, setPrettyString is not required anymore.
  * Changed: `AbstractConstraint` marked deprecated, will be removed in 2.0.
  * Changed: `Constraint` is now extensible.

### [1.0.0] 2015-09-21

  * Break: `VersionConstraint` renamed to `Constraint`.
  * Break: `SpecificConstraint` renamed to `AbstractConstraint`.
  * Break: `LinkConstraintInterface` renamed to `ConstraintInterface`.
  * Break: `VersionParser::parseNameVersionPairs` was removed.
  * Changed: `VersionParser::parseConstraints` allows (but ignores) build metadata now.
  * Changed: `VersionParser::parseConstraints` allows (but ignores) prefixing numeric versions with a 'v' now.
  * Changed: Fixed namespace(s) of test files.
  * Changed: `Comparator::compare` no longer throws `InvalidArgumentException`.
  * Changed: `Constraint` now throws `InvalidArgumentException`.

### [0.1.0] 2015-07-23

  * Added: `Composer\Semver\Comparator`, various methods to compare versions.
  * Added: various documents such as README.md, LICENSE, etc.
  * Added: configuration files for Git, Travis, php-cs-fixer, phpunit.
  * Break: the following namespaces were renamed:
    - Namespace: `Composer\Package\Version` -> `Composer\Semver`
    - Namespace: `Composer\Package\LinkConstraint` -> `Composer\Semver\Constraint`
    - Namespace: `Composer\Test\Package\Version` -> `Composer\Test\Semver`
    - Namespace: `Composer\Test\Package\LinkConstraint` -> `Composer\Test\Semver\Constraint`
  * Changed: code style using php-cs-fixer.

[1.7.1]: https://github.com/composer/semver/compare/1.7.0...1.7.1
[1.7.0]: https://github.com/composer/semver/compare/1.6.0...1.7.0
[1.6.0]: https://github.com/composer/semver/compare/1.5.2...1.6.0
[1.5.2]: https://github.com/composer/semver/compare/1.5.1...1.5.2
[1.5.1]: https://github.com/composer/semver/compare/1.5.0...1.5.1
[1.5.0]: https://github.com/composer/semver/compare/1.4.2...1.5.0
[1.4.2]: https://github.com/composer/semver/compare/1.4.1...1.4.2
[1.4.1]: https://github.com/composer/semver/compare/1.4.0...1.4.1
[1.4.0]: https://github.com/composer/semver/compare/1.3.0...1.4.0
[1.3.0]: https://github.com/composer/semver/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/composer/semver/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/composer/semver/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/composer/semver/compare/0.1.0...1.0.0
[0.1.0]: https://github.com/composer/semver/compare/5e0b9a4da...0.1.0
