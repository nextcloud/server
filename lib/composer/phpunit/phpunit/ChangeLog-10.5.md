# Changes in PHPUnit 10.5

All notable changes of the PHPUnit 10.5 release series are documented in this file using the [Keep a CHANGELOG](https://keepachangelog.com/) principles.

## [10.5.51] - 2025-08-12

### Changed

* [#6308](https://github.com/sebastianbergmann/phpunit/pull/6308): Improve output of `--check-php-configuration`
* The version number for the test result cache file has been incremented to reflect that its structure for PHPUnit 10.5 is not compatible with its structure for PHPUnit 8.5 and PHPUnit 9.6

## [10.5.50] - 2025-08-10

### Changed

* [#6300](https://github.com/sebastianbergmann/phpunit/issues/6300): Emit warning when the name of a data provider method begins with `test`
* Do not use `SplObjectStorage` methods that will be deprecated in PHP 8.5

## [10.5.49] - 2025-08-09

### Added

* [#6297](https://github.com/sebastianbergmann/phpunit/issues/6297): `--check-php-configuration` CLI option for checking whether PHP is configured for testing

### Fixed

* Errors due to invalid data provided using `#[TestWith]` or `#[TestWithJson]` attributes are now properly reported

## [10.5.48] - 2025-07-11

### Fixed

* [#6254](https://github.com/sebastianbergmann/phpunit/issues/6254): `defects,random`configuration is supported by implementation, but it is not allowed by the XML configuration file schema

## [10.5.47] - 2025-06-20

### Added

* [#6236](https://github.com/sebastianbergmann/phpunit/issues/6236): `failOnPhpunitWarning` attribute on the `<phpunit>` element of the XML configuration file and `--fail-on-phpunit-warning` CLI option for controlling whether PHPUnit should fail on PHPUnit warnings (default: `true`)
* [#6239](https://github.com/sebastianbergmann/phpunit/issues/6239): `--do-not-fail-on-deprecation`, `--do-not-fail-on-phpunit-warning`, `--do-not-fail-on-phpunit-deprecation`, `--do-not-fail-on-empty-test-suite`, `--do-not-fail-on-incomplete`, `--do-not-fail-on-notice`, `--do-not-fail-on-risky`, `--do-not-fail-on-skipped`, and `--do-not-fail-on-warning` CLI options
* `--do-not-report-useless-tests` CLI option as a replacement for `--dont-report-useless-tests`

### Deprecated

* `--dont-report-useless-tests` CLI option (use `--do-not-report-useless-tests` instead)

### Fixed

* [#6243](https://github.com/sebastianbergmann/phpunit/issues/6243): Constraints cannot be implemented without using internal class `ExpectationFailedException`

## [10.5.46] - 2025-05-02

### Added

* `displayDetailsOnAllIssues` attribute on the `<phpunit>` element of the XML configuration file and `--display-all-issues` CLI option for controlling whether PHPUnit should display details on all issues that are triggered (default: `false`)
* `failOnAllIssues` attribute on the `<phpunit>` element of the XML configuration file and `--fail-on-all-issues` CLI option for controlling whether PHPUnit should fail on all issues that are triggered (default: `false`)

### Changed

* [#5956](https://github.com/sebastianbergmann/phpunit/issues/5956): Improved handling of deprecated `E_STRICT` constant
* Improved message when test is considered risky for printing unexpected output

## [10.5.45] - 2025-02-06

### Changed

* [#6117](https://github.com/sebastianbergmann/phpunit/issues/6117): Include source location information for issues triggered during test in `--debug` output
* [#6119](https://github.com/sebastianbergmann/phpunit/issues/6119): Improve message for errors that occur while parsing attributes

## [10.5.44] - 2025-01-31

### Fixed

* [#6115](https://github.com/sebastianbergmann/phpunit/issues/6115): Backed enumerations with values not of type `string` cannot be used in customized TestDox output

## [10.5.43] - 2025-01-29

### Changed

* Do not skip execution of test that depends on a test that is larger than itself

## [10.5.42] - 2025-01-28

### Fixed

* [#6103](https://github.com/sebastianbergmann/phpunit/issues/6103): Output from test run in separate process is printed twice
* [#6109](https://github.com/sebastianbergmann/phpunit/issues/6109): Skipping a test in a before-class method crashes JUnit XML logger
* [#6111](https://github.com/sebastianbergmann/phpunit/issues/6111): Deprecations cause `SourceMapper` to scan all `<source/>` files

## [10.5.41] - 2025-01-13

### Added

* `Test\AfterLastTestMethodErrored`, `Test\AfterTestMethodErrored`, `Test\BeforeTestMethodErrored`, `Test\PostConditionErrored`, and `Test\PreConditionErrored` events

### Fixed

* [#6094](https://github.com/sebastianbergmann/phpunit/issues/6094): Errors in after-last-test methods are not reported
* [#6095](https://github.com/sebastianbergmann/phpunit/issues/6095): Expectation is not counted correctly when a doubled method is called more often than is expected
* [#6098](https://github.com/sebastianbergmann/phpunit/issues/6098): No `system-out` element in JUnit XML logfile

## [10.5.40] - 2024-12-21

### Fixed

* [#6082](https://github.com/sebastianbergmann/phpunit/issues/6082): `assertArrayHasKey()`, `assertArrayNotHasKey()`, `arrayHasKey()`, and `ArrayHasKey::__construct()` do not support all possible key types
* [#6087](https://github.com/sebastianbergmann/phpunit/issues/6087): `--migrate-configuration` does not remove `beStrictAboutTodoAnnotatedTests` attribute from XML configuration file

## [10.5.39] - 2024-12-11

### Added

* [#6081](https://github.com/sebastianbergmann/phpunit/pull/6081): `DefaultResultCache::mergeWith()` for merging result cache instances

### Fixed

* [#6066](https://github.com/sebastianbergmann/phpunit/pull/6066): TeamCity logger does not handle error/skipped events in before-class methods correctly

## [10.5.38] - 2024-10-28

### Changed

* [#6012](https://github.com/sebastianbergmann/phpunit/pull/6012): Remove empty lines between TeamCity events

## [10.5.37] - 2024-10-19

### Fixed

* [#5982](https://github.com/sebastianbergmann/phpunit/pull/5982): Typo in exception message

## [10.5.36] - 2024-10-08

### Changed

* [#5957](https://github.com/sebastianbergmann/phpunit/pull/5957): Skip data provider build when requirements are not satisfied
* [#5969](https://github.com/sebastianbergmann/phpunit/pull/5969): Check for requirements before creating a separate process
* Updated regular expressions used by `StringMatchesFormatDescription` constraint to be consistent with PHP's `run-tests.php`

### Fixed

* [#5965](https://github.com/sebastianbergmann/phpunit/issues/5965): `PHPUnit\Framework\Exception` does not handle string error codes (`PDOException` with error code `'HY000'`, for example)

## [10.5.35] - 2024-09-19

### Changed

* [#5956](https://github.com/sebastianbergmann/phpunit/issues/5956): Deprecation of the `E_STRICT` constant in PHP 8.4

### Fixed

* [#5950](https://github.com/sebastianbergmann/phpunit/pull/5950): TestDox text should not be `trim()`med when it contains `$` character
* The attribute parser will no longer try to instantiate attribute classes that do not exist

## [10.5.34] - 2024-09-13

### Fixed

* [#5931](https://github.com/sebastianbergmann/phpunit/pull/5931): Reverted addition of `name` property on `<testsuites>` element in JUnit XML logfile
* [#5946](https://github.com/sebastianbergmann/phpunit/issues/5946): `Callback` throws a `TypeError` when checking a `callable` has variadic parameters

## [10.5.33] - 2024-09-09

### Fixed

* [#4584](https://github.com/sebastianbergmann/phpunit/issues/4584): `assertJsonStringEqualsJsonString()` considers objects with sequential numeric keys equal to be arrays
* [#4625](https://github.com/sebastianbergmann/phpunit/issues/4625): Generator yielding keys that are neither integer or string leads to hard-to-understand error message when used as data provider
* [#4674](https://github.com/sebastianbergmann/phpunit/issues/4674): JSON assertions should treat objects as unordered
* [#5891](https://github.com/sebastianbergmann/phpunit/issues/5891): `Callback` constraint does not handle variadic arguments correctly when used for mock object expectations
* [#5929](https://github.com/sebastianbergmann/phpunit/issues/5929): TestDox output containing `$` at the beginning gets truncated when used with a data provider

## [10.5.32] - 2024-09-04

### Added

* [#5937](https://github.com/sebastianbergmann/phpunit/issues/5937): `failOnPhpunitDeprecation` attribute on the `<phpunit>` element of the XML configuration file and `--fail-on-phpunit-deprecation` CLI option for controlling whether PHPUnit deprecations should be considered when determining the test runner's shell exit code (default: do not consider)
* `displayDetailsOnPhpunitDeprecations` attribute on the `<phpunit>` element of the XML configuration file and `--display-phpunit-deprecations` CLI option for controlling whether details on PHPUnit deprecations should be displayed (default: do not display)

### Changed

* [#5937](https://github.com/sebastianbergmann/phpunit/issues/5937): PHPUnit deprecations will, by default, no longer affect the test runner's shell exit code. This can optionally be turned back on using the `--fail-on-phpunit-deprecation` CLI option or the `failOnPhpunitDeprecation="true"` attribute on the `<phpunit>` element of the XML configuration file.
* Details for PHPUnit deprecations will, by default, no longer be displayed. This can optionally be turned back on using the `--display-phpunit-deprecations` CLI option or the `displayDetailsOnPhpunitDeprecations` attribute on the `<phpunit>` element of the XML configuration file.

## [10.5.31] - 2024-09-03

### Changed

* [#5931](https://github.com/sebastianbergmann/phpunit/pull/5931): `name` property on `<testsuites>` element in JUnit XML logfile
* Removed `.phpstorm.meta.php` file as methods such as `TestCase::createStub()` use generics / template types for their return types and PhpStorm, for example, uses that information

### Fixed

* [#5884](https://github.com/sebastianbergmann/phpunit/issues/5884): TestDox printer does not consider that issues can be suppressed by attribute, baseline, source location, or `@` operator

## [10.5.30] - 2024-08-13

### Changed

* Improved error message when stubbed method is called more often than return values were configured for it

## [10.5.29] - 2024-07-30

### Fixed

* [#5887](https://github.com/sebastianbergmann/phpunit/pull/5887): Issue baseline generator does not correctly handle ignoring suppressed issues
* [#5908](https://github.com/sebastianbergmann/phpunit/issues/5908): `--list-tests` and `--list-tests-xml` CLI options do not report error when data provider method throws exception

## [10.5.28] - 2024-07-18

### Fixed

* [#5898](https://github.com/sebastianbergmann/phpunit/issues/5898): `Test\Passed` event is not emitted for PHPT tests
* `--coverage-filter` CLI option could not be used multiple times

## [10.5.27] - 2024-07-10

### Changed

* Updated dependencies (so that users that install using Composer's `--prefer-lowest` CLI option also get recent versions)

### Fixed

* [#5892](https://github.com/sebastianbergmann/phpunit/issues/5892): Errors during write of `phpunit.xml` are not handled correctly when `--generate-configuration` is used

## [10.5.26] - 2024-07-08

### Added

* `--only-summary-for-coverage-text` CLI option to reduce the code coverage report in text format to a summary
* `--show-uncovered-for-coverage-text` CLI option to expand the code coverage report in text format to include a list of uncovered files

## [10.5.25] - 2024-07-03

### Changed

* Updated dependencies for PHAR distribution

## [10.5.24] - 2024-06-20

### Changed

* [#5877](https://github.com/sebastianbergmann/phpunit/pull/5877): Use `array_pop()` instead of `array_shift()` for processing `Test` objects in `TestSuite::run()` and optimize `TestSuite::isEmpty()`

## [10.5.23] - 2024-06-20

### Changed

* [#5875](https://github.com/sebastianbergmann/phpunit/pull/5875): Also destruct `TestCase` objects early that use a data provider

## [10.5.22] - 2024-06-19

### Changed

* [#5871](https://github.com/sebastianbergmann/phpunit/pull/5871): Do not collect unnecessary information using `debug_backtrace()`

## [10.5.21] - 2024-06-15

### Changed

* [#5861](https://github.com/sebastianbergmann/phpunit/pull/5861): Destroy `TestCase` object after its test was run

## [10.5.20] - 2024-04-24

* [#5771](https://github.com/sebastianbergmann/phpunit/issues/5771): JUnit XML logger may crash when test that is run in separate process exits unexpectedly
* [#5819](https://github.com/sebastianbergmann/phpunit/issues/5819): Duplicate keys from different data providers are not handled properly

## [10.5.19] - 2024-04-17

### Fixed

* [#5818](https://github.com/sebastianbergmann/phpunit/issues/5818): Calling `method()` on a test stub created using `createStubForIntersectionOfInterfaces()` throws an unexpected exception

## [10.5.18] - 2024-04-14

### Deprecated

* [#5812](https://github.com/sebastianbergmann/phpunit/pull/5812): Support for string array keys in data sets returned by data provider methods that do not match the parameter names of the test method(s) that use(s) them

### Fixed

* [#5795](https://github.com/sebastianbergmann/phpunit/issues/5795): Using `@testWith` annotation may generate `PHP Warning:  Uninitialized string offset 0`

## [10.5.17] - 2024-04-05

### Changed

* The namespaces of dependencies are now prefixed with `PHPUnitPHAR` instead of just `PHPUnit` for the PHAR distribution of PHPUnit

## [10.5.16] - 2024-03-28

### Changed

* [#5766](https://github.com/sebastianbergmann/phpunit/pull/5766): Do not use a shell in `proc_open()` if not really needed
* [#5772](https://github.com/sebastianbergmann/phpunit/pull/5772): Cleanup process handling after dropping temp-file handling

### Fixed

* [#5570](https://github.com/sebastianbergmann/phpunit/pull/5570): Windows does not support exclusive locks on stdout

## [10.5.15] - 2024-03-22

### Fixed

* [#5765](https://github.com/sebastianbergmann/phpunit/pull/5765): Be more forgiving with error handlers that do not respect error suppression

## [10.5.14] - 2024-03-21

### Changed

* [#5747](https://github.com/sebastianbergmann/phpunit/pull/5747): Cache result of `Groups::groups()`
* [#5748](https://github.com/sebastianbergmann/phpunit/pull/5748): Improve performance of `NamePrettifier::prettifyTestMethodName()`
* [#5750](https://github.com/sebastianbergmann/phpunit/pull/5750): Micro-optimize `NamePrettifier::prettifyTestMethodName()` once again

### Fixed

* [#5760](https://github.com/sebastianbergmann/phpunit/issues/5760): TestDox printer does not display details about exceptions raised in before-test methods

## [10.5.13] - 2024-03-12

### Changed

* [#5727](https://github.com/sebastianbergmann/phpunit/pull/5727): Prevent duplicate call of `NamePrettifier::prettifyTestMethodName()`
* [#5739](https://github.com/sebastianbergmann/phpunit/pull/5739): Micro-optimize `NamePrettifier::prettifyTestMethodName()`
* [#5740](https://github.com/sebastianbergmann/phpunit/pull/5740): Micro-optimize `TestRunner::runTestWithTimeout()`
* [#5741](https://github.com/sebastianbergmann/phpunit/pull/5741): Save call to `Telemetry\System::snapshot()`
* [#5742](https://github.com/sebastianbergmann/phpunit/pull/5742): Prevent file IO when not strictly necessary
* [#5743](https://github.com/sebastianbergmann/phpunit/pull/5743): Prevent unnecessary `ExecutionOrderDependency::getTarget()` call
* [#5744](https://github.com/sebastianbergmann/phpunit/pull/5744): Simplify `NamePrettifier::prettifyTestMethodName()`

### Fixed

* [#5351](https://github.com/sebastianbergmann/phpunit/issues/5351): Incorrect code coverage metadata does not prevent code coverage data from being collected
* [#5746](https://github.com/sebastianbergmann/phpunit/issues/5746): Using `-d` CLI option multiple times triggers warning

## [10.5.12] - 2024-03-09

### Fixed

* [#5652](https://github.com/sebastianbergmann/phpunit/issues/5652): `HRTime::duration()` throws `InvalidArgumentException`

## [10.5.11] - 2024-02-25

### Fixed

* [#5704](https://github.com/sebastianbergmann/phpunit/issues/5704#issuecomment-1951105254): No warning when CLI options are used multiple times
* [#5707](https://github.com/sebastianbergmann/phpunit/issues/5707): `--fail-on-empty-test-suite` CLI option is not documented in `--help` output
* No warning when the `#[CoversClass]` and `#[UsesClass]` attributes are used with the name of an interface
* Resource usage information is printed when the `--debug` CLI option is used

## [10.5.10] - 2024-02-04

### Changed

* Improve output of `--check-version` CLI option
* Improve description of `--check-version` CLI option

### Fixed

* [#5692](https://github.com/sebastianbergmann/phpunit/issues/5692): `--log-events-text` and `--log-events-verbose-text` require the destination file to exit

## [10.5.9] - 2024-01-22

### Changed

* Show help for `--manifest`, `--sbom`, and `--composer-lock` when the PHAR is used

### Fixed

* [#5676](https://github.com/sebastianbergmann/phpunit/issues/5676): PHPUnit's test runner overwrites custom error handler registered using `set_error_handler()` in bootstrap script

## [10.5.8] - 2024-01-19

### Fixed

* [#5673](https://github.com/sebastianbergmann/phpunit/issues/5673): Confusing error message when migration of a configuration is requested that does not need to be migrated

## [10.5.7] - 2024-01-14

### Fixed

* [#5662](https://github.com/sebastianbergmann/phpunit/issues/5662): PHPUnit errors out on startup when the `ctype` extension is not loaded but a polyfill for it was installed

## [10.5.6] - 2024-01-13

### Added

* Added the `--debug` CLI option as an alias for `--no-output --log-events-text php://stdout`

### Fixed

* [#5455](https://github.com/sebastianbergmann/phpunit/issues/5455): `willReturnCallback()` does not pass unknown named variadic arguments to callback
* [#5488](https://github.com/sebastianbergmann/phpunit/issues/5488): Details about tests that are considered risky are not displayed when the TestDox result printer is used
* [#5516](https://github.com/sebastianbergmann/phpunit/issues/5516): Assertions that use the `LogicalNot` constraint (`assertNotEquals()`, `assertStringNotContainsString()`, ...) can generate confusing failure messages
* [#5518](https://github.com/sebastianbergmann/phpunit/issues/5518): Details about deprecations, notices, and warnings are not displayed when the TestDox result printer is used
* [#5574](https://github.com/sebastianbergmann/phpunit/issues/5574): Wrong backtrace line is reported
* [#5633](https://github.com/sebastianbergmann/phpunit/pull/5633): `--log-events-text` and `--log-events-verbose-text` CLI options do not handle absolute and relative paths
* [#5634](https://github.com/sebastianbergmann/phpunit/pull/5634): Exceptions in the destructor of a test double are ignored
* [#5641](https://github.com/sebastianbergmann/phpunit/issues/5641): The `TestSuite` value object returned by `TestSuite\Filtered::testSuite()` contains all tests instead of only the filtered tests

## [10.5.5] - 2023-12-27

### Fixed

* [#5619](https://github.com/sebastianbergmann/phpunit/pull/5619): Reverted change introduced in PHPUnit 10.5.4 that broke backward compatibility

## [10.5.4] - 2023-12-27

### Fixed

* [#5592](https://github.com/sebastianbergmann/phpunit/issues/5592): Error Handler prevents `error_get_last()` usage in tests
* [#5592](https://github.com/sebastianbergmann/phpunit/issues/5592): `E_USER_ERROR` does not abort test execution
* [#5612](https://github.com/sebastianbergmann/phpunit/issues/5612): Empty `<coverage>` element in XML configuration after migrating configuration
* [#5616](https://github.com/sebastianbergmann/phpunit/issues/5616): Values from data provider are not shown for failed test
* [#5619](https://github.com/sebastianbergmann/phpunit/pull/5619): Check and restore error/exception global handlers
* [#5621](https://github.com/sebastianbergmann/phpunit/issues/5621): Name of data set is missing from TeamCity output

## [10.5.3] - 2023-12-13

### Changed

* Make PHAR build reproducible (the only remaining differences were in the timestamps for the files in the PHAR)

### Deprecated

* `Test\AssertionFailed` and `Test\AssertionSucceeded` events
* `PHPUnit\Runner\Extension\Facade::requireExportOfObjects()` and `PHPUnit\Runner\Extension\Facade::requiresExportOfObjects()`
* `registerMockObjectsFromTestArgumentsRecursively` attribute on the `<phpunit>` element of the XML configuration file
* `PHPUnit\TextUI\Configuration\Configuration::registerMockObjectsFromTestArgumentsRecursively()`

### Fixed

* [#5614](https://github.com/sebastianbergmann/phpunit/issues/5614): Infinite recursion when data provider provides recursive array

## [10.5.2] - 2023-12-05

### Fixed

* [#5561](https://github.com/sebastianbergmann/phpunit/issues/5561): JUnit XML logger does not handle assertion failures in before-test methods
* [#5567](https://github.com/sebastianbergmann/phpunit/issues/5567): Infinite recursion when recursive / self-referencing arrays are checked whether they contain only scalar values

## [10.5.1] - 2023-12-01

### Fixed

* [#5593](https://github.com/sebastianbergmann/phpunit/issues/5593): Return Value Generator fails to correctly create test stub for method with `static` return type declaration when used recursively
* [#5596](https://github.com/sebastianbergmann/phpunit/issues/5596): `PHPUnit\Framework\TestCase` has `@internal` annotation in PHAR

## [10.5.0] - 2023-12-01

### Added

* [#5532](https://github.com/sebastianbergmann/phpunit/issues/5532): `#[IgnoreDeprecations]` attribute to ignore `E_(USER_)DEPRECATED` issues on test class and test method level
* [#5551](https://github.com/sebastianbergmann/phpunit/issues/5551): Support for omitting parameter default values for `willReturnMap()` 
* [#5577](https://github.com/sebastianbergmann/phpunit/issues/5577): `--composer-lock` CLI option for PHAR binary that displays the `composer.lock` used to build the PHAR 

### Changed

* `MockBuilder::disableAutoReturnValueGeneration()` and `MockBuilder::enableAutoReturnValueGeneration()` are no longer deprecated

### Fixed

* [#5563](https://github.com/sebastianbergmann/phpunit/issues/5563): `createMockForIntersectionOfInterfaces()` does not automatically register mock object for expectation verification

[10.5.51]: https://github.com/sebastianbergmann/phpunit/compare/10.5.50...10.5.51
[10.5.50]: https://github.com/sebastianbergmann/phpunit/compare/10.5.49...10.5.50
[10.5.49]: https://github.com/sebastianbergmann/phpunit/compare/10.5.48...10.5.49
[10.5.48]: https://github.com/sebastianbergmann/phpunit/compare/10.5.47...10.5.48
[10.5.47]: https://github.com/sebastianbergmann/phpunit/compare/10.5.46...10.5.47
[10.5.46]: https://github.com/sebastianbergmann/phpunit/compare/10.5.45...10.5.46
[10.5.45]: https://github.com/sebastianbergmann/phpunit/compare/10.5.44...10.5.45
[10.5.44]: https://github.com/sebastianbergmann/phpunit/compare/10.5.43...10.5.44
[10.5.43]: https://github.com/sebastianbergmann/phpunit/compare/10.5.42...10.5.43
[10.5.42]: https://github.com/sebastianbergmann/phpunit/compare/10.5.41...10.5.42
[10.5.41]: https://github.com/sebastianbergmann/phpunit/compare/10.5.40...10.5.41
[10.5.40]: https://github.com/sebastianbergmann/phpunit/compare/10.5.39...10.5.40
[10.5.39]: https://github.com/sebastianbergmann/phpunit/compare/10.5.38...10.5.39
[10.5.38]: https://github.com/sebastianbergmann/phpunit/compare/10.5.37...10.5.38
[10.5.37]: https://github.com/sebastianbergmann/phpunit/compare/10.5.36...10.5.37
[10.5.36]: https://github.com/sebastianbergmann/phpunit/compare/10.5.35...10.5.36
[10.5.35]: https://github.com/sebastianbergmann/phpunit/compare/10.5.34...10.5.35
[10.5.34]: https://github.com/sebastianbergmann/phpunit/compare/10.5.33...10.5.34
[10.5.33]: https://github.com/sebastianbergmann/phpunit/compare/10.5.32...10.5.33
[10.5.32]: https://github.com/sebastianbergmann/phpunit/compare/10.5.31...10.5.32
[10.5.31]: https://github.com/sebastianbergmann/phpunit/compare/10.5.30...10.5.31
[10.5.30]: https://github.com/sebastianbergmann/phpunit/compare/10.5.29...10.5.30
[10.5.29]: https://github.com/sebastianbergmann/phpunit/compare/10.5.28...10.5.29
[10.5.28]: https://github.com/sebastianbergmann/phpunit/compare/10.5.27...10.5.28
[10.5.27]: https://github.com/sebastianbergmann/phpunit/compare/10.5.26...10.5.27
[10.5.26]: https://github.com/sebastianbergmann/phpunit/compare/10.5.25...10.5.26
[10.5.25]: https://github.com/sebastianbergmann/phpunit/compare/10.5.24...10.5.25
[10.5.24]: https://github.com/sebastianbergmann/phpunit/compare/10.5.23...10.5.24
[10.5.23]: https://github.com/sebastianbergmann/phpunit/compare/10.5.22...10.5.23
[10.5.22]: https://github.com/sebastianbergmann/phpunit/compare/10.5.21...10.5.22
[10.5.21]: https://github.com/sebastianbergmann/phpunit/compare/10.5.20...10.5.21
[10.5.20]: https://github.com/sebastianbergmann/phpunit/compare/10.5.19...10.5.20
[10.5.19]: https://github.com/sebastianbergmann/phpunit/compare/10.5.18...10.5.19
[10.5.18]: https://github.com/sebastianbergmann/phpunit/compare/10.5.17...10.5.18
[10.5.17]: https://github.com/sebastianbergmann/phpunit/compare/10.5.16...10.5.17
[10.5.16]: https://github.com/sebastianbergmann/phpunit/compare/10.5.15...10.5.16
[10.5.15]: https://github.com/sebastianbergmann/phpunit/compare/10.5.14...10.5.15
[10.5.14]: https://github.com/sebastianbergmann/phpunit/compare/10.5.13...10.5.14
[10.5.13]: https://github.com/sebastianbergmann/phpunit/compare/10.5.12...10.5.13
[10.5.12]: https://github.com/sebastianbergmann/phpunit/compare/10.5.11...10.5.12
[10.5.11]: https://github.com/sebastianbergmann/phpunit/compare/10.5.10...10.5.11
[10.5.10]: https://github.com/sebastianbergmann/phpunit/compare/10.5.9...10.5.10
[10.5.9]: https://github.com/sebastianbergmann/phpunit/compare/10.5.8...10.5.9
[10.5.8]: https://github.com/sebastianbergmann/phpunit/compare/10.5.7...10.5.8
[10.5.7]: https://github.com/sebastianbergmann/phpunit/compare/10.5.6...10.5.7
[10.5.6]: https://github.com/sebastianbergmann/phpunit/compare/10.5.5...10.5.6
[10.5.5]: https://github.com/sebastianbergmann/phpunit/compare/10.5.4...10.5.5
[10.5.4]: https://github.com/sebastianbergmann/phpunit/compare/10.5.3...10.5.4
[10.5.3]: https://github.com/sebastianbergmann/phpunit/compare/10.5.2...10.5.3
[10.5.2]: https://github.com/sebastianbergmann/phpunit/compare/10.5.1...10.5.2
[10.5.1]: https://github.com/sebastianbergmann/phpunit/compare/10.5.0...10.5.1
[10.5.0]: https://github.com/sebastianbergmann/phpunit/compare/10.4.2...10.5.0
