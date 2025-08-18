# Deprecations

## Soft Deprecations

This functionality is currently [soft-deprecated](https://phpunit.de/backward-compatibility.html#soft-deprecation):

### Writing Tests

#### Assertions, Constraints, and Expectations

| Issue                                                             | Description                                  | Since  | Replacement |
|-------------------------------------------------------------------|----------------------------------------------|--------|-------------|
| [#5472](https://github.com/sebastianbergmann/phpunit/issues/5472) | `Assert::assertStringNotMatchesFormat()`     | 10.4.0 |             |
| [#5472](https://github.com/sebastianbergmann/phpunit/issues/5472) | `Assert::assertStringNotMatchesFormatFile()` | 10.4.0 |             |

#### Test Double API

| Issue                                                             | Description                                       | Since  | Replacement                                                                             |
|-------------------------------------------------------------------|---------------------------------------------------|--------|-----------------------------------------------------------------------------------------|
| [#5240](https://github.com/sebastianbergmann/phpunit/issues/5240) | `TestCase::createTestProxy()`                     | 10.1.0 |                                                                                         |
| [#5241](https://github.com/sebastianbergmann/phpunit/issues/5241) | `TestCase::getMockForAbstractClass()`             | 10.1.0 |                                                                                         |
| [#5242](https://github.com/sebastianbergmann/phpunit/issues/5242) | `TestCase::getMockFromWsdl()`                     | 10.1.0 |                                                                                         |
| [#5243](https://github.com/sebastianbergmann/phpunit/issues/5243) | `TestCase::getMockForTrait()`                     | 10.1.0 |                                                                                         |
| [#5244](https://github.com/sebastianbergmann/phpunit/issues/5244) | `TestCase::getObjectForTrait()`                   | 10.1.0 |                                                                                         |
| [#5305](https://github.com/sebastianbergmann/phpunit/issues/5305) | `MockBuilder::getMockForAbstractClass()`          | 10.1.0 |                                                                                         |
| [#5306](https://github.com/sebastianbergmann/phpunit/issues/5306) | `MockBuilder::getMockForTrait()`                  | 10.1.0 |                                                                                         |
| [#5307](https://github.com/sebastianbergmann/phpunit/issues/5307) | `MockBuilder::disableProxyingToOriginalMethods()` | 10.1.0 |                                                                                         |
| [#5307](https://github.com/sebastianbergmann/phpunit/issues/5307) | `MockBuilder::enableProxyingToOriginalMethods()`  | 10.1.0 |                                                                                         |
| [#5307](https://github.com/sebastianbergmann/phpunit/issues/5307) | `MockBuilder::setProxyTarget()`                   | 10.1.0 |                                                                                         |
| [#5308](https://github.com/sebastianbergmann/phpunit/issues/5308) | `MockBuilder::allowMockingUnknownTypes()`         | 10.1.0 |                                                                                         |
| [#5308](https://github.com/sebastianbergmann/phpunit/issues/5308) | `MockBuilder::disallowMockingUnknownTypes()`      | 10.1.0 |                                                                                         |
| [#5309](https://github.com/sebastianbergmann/phpunit/issues/5309) | `MockBuilder::disableAutoload()`                  | 10.1.0 |                                                                                         |
| [#5309](https://github.com/sebastianbergmann/phpunit/issues/5309) | `MockBuilder::enableAutoload()`                   | 10.1.0 |                                                                                         |
| [#5315](https://github.com/sebastianbergmann/phpunit/issues/5315) | `MockBuilder::disableArgumentCloning()`           | 10.1.0 |                                                                                         |
| [#5315](https://github.com/sebastianbergmann/phpunit/issues/5315) | `MockBuilder::enableArgumentCloning()`            | 10.1.0 |                                                                                         |
| [#5320](https://github.com/sebastianbergmann/phpunit/issues/5320) | `MockBuilder::addMethods()`                       | 10.1.0 |                                                                                         |
| [#5423](https://github.com/sebastianbergmann/phpunit/issues/5423) | `TestCase::onConsecutiveCalls()`                  | 10.3.0 | Use `$double->willReturn()` instead of `$double->will($this->onConsecutiveCalls())`     |
| [#5423](https://github.com/sebastianbergmann/phpunit/issues/5423) | `TestCase::returnArgument()`                      | 10.3.0 | Use `$double->willReturnArgument()` instead of `$double->will($this->returnArgument())` |
| [#5423](https://github.com/sebastianbergmann/phpunit/issues/5423) | `TestCase::returnCallback()`                      | 10.3.0 | Use `$double->willReturnCallback()` instead of `$double->will($this->returnCallback())` |
| [#5423](https://github.com/sebastianbergmann/phpunit/issues/5423) | `TestCase::returnSelf()`                          | 10.3.0 | Use `$double->willReturnSelf()` instead of `$double->will($this->returnSelf())`         |
| [#5423](https://github.com/sebastianbergmann/phpunit/issues/5423) | `TestCase::returnValue()`                         | 10.3.0 | Use `$double->willReturn()` instead of `$double->will($this->returnValue())`            |
| [#5423](https://github.com/sebastianbergmann/phpunit/issues/5423) | `TestCase::returnValueMap()`                      | 10.3.0 | Use `$double->willReturnMap()` instead of `$double->will($this->returnValueMap())`      |

#### Miscellaneous

| Issue                                                             | Description                                                    | Since  | Replacement                                                        |
|-------------------------------------------------------------------|----------------------------------------------------------------|--------|--------------------------------------------------------------------|
| [#5236](https://github.com/sebastianbergmann/phpunit/issues/5236) | `PHPUnit\Framework\Attributes\CodeCoverageIgnore()`            | 10.1.0 |                                                                    |
| [#5214](https://github.com/sebastianbergmann/phpunit/issues/5214) | `TestCase::iniSet()`                                           | 10.3.0 |                                                                    |
| [#5216](https://github.com/sebastianbergmann/phpunit/issues/5216) | `TestCase::setLocale()`                                        | 10.3.0 |                                                                    |
| [#5236](https://github.com/sebastianbergmann/phpunit/issues/5513) | `PHPUnit\Framework\Attributes\IgnoreClassForCodeCoverage()`    | 10.4.0 | Use `@codeCoverageIgnore` annotation in the class' doc-comment     |
| [#5236](https://github.com/sebastianbergmann/phpunit/issues/5513) | `PHPUnit\Framework\Attributes\IgnoreMethodForCodeCoverage()`   | 10.4.0 | Use `@codeCoverageIgnore` annotation in the method's doc-comment   |
| [#5236](https://github.com/sebastianbergmann/phpunit/issues/5513) | `PHPUnit\Framework\Attributes\IgnoreFunctionForCodeCoverage()` | 10.4.0 | Use `@codeCoverageIgnore` annotation in the function's doc-comment |

### Running Tests

| Issue                                                             | Description                                                                                           | Since  | Replacement |
|-------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------|--------|-------------|
| [#5481](https://github.com/sebastianbergmann/phpunit/issues/5481) | `dataSet` attribute for `testCaseMethod` elements in the XML document generated by `--list-tests-xml` | 10.4.0 |             |

### Extending PHPUnit

| Issue | Description                                                                                                                  | Since  | Replacement                                                                    |
|-------|------------------------------------------------------------------------------------------------------------------------------|--------|--------------------------------------------------------------------------------|
|       | `PHPUnit\TextUI\Configuration\Configuration::coverageExcludeDirectories()`                                                   | 10.2.0 | `PHPUnit\TextUI\Configuration\Configuration::source()->excludeDirectories()`   |
|       | `PHPUnit\TextUI\Configuration\Configuration::coverageExcludeFiles()`                                                         | 10.2.0 | `PHPUnit\TextUI\Configuration\Configuration::source()->excludeFiles()`         |
|       | `PHPUnit\TextUI\Configuration\Configuration::coverageIncludeDirectories()`                                                   | 10.2.0 | `PHPUnit\TextUI\Configuration\Configuration::source()->includeDirectories()`   |
|       | `PHPUnit\TextUI\Configuration\Configuration::coverageIncludeFiles()`                                                         | 10.2.0 | `PHPUnit\TextUI\Configuration\Configuration::source()->includeFiles()`         |
|       | `PHPUnit\TextUI\Configuration\Configuration::loadPharExtensions()`                                                           | 10.2.0 | `PHPUnit\TextUI\Configuration\Configuration::noExtensions()`                   |
|       | `PHPUnit\TextUI\Configuration\Configuration::hasNonEmptyListOfFilesToBeIncludedInCodeCoverageReport()`                       | 10.2.0 | `PHPUnit\TextUI\Configuration\Configuration::source()->notEmpty()`             |
|       | `PHPUnit\TextUI\Configuration\Configuration::restrictDeprecations()`                                                         | 10.2.0 | `PHPUnit\TextUI\Configuration\Configuration::source()->restrictDeprecations()` |
|       | `PHPUnit\TextUI\Configuration\Configuration::restrictNotices()`                                                              | 10.2.0 | `PHPUnit\TextUI\Configuration\Configuration::source()->restrictNotices()`      |
|       | `PHPUnit\TextUI\Configuration\Configuration::restrictWarnings()`                                                             | 10.2.0 | `PHPUnit\TextUI\Configuration\Configuration::source()->restrictWarnings()`     |
|       | `PHPUnit\TextUI\Configuration\Configuration::cliArgument()`                                                                  | 10.4.0 | `PHPUnit\TextUI\Configuration\Configuration::cliArguments()[0]`                |
|       | `PHPUnit\TextUI\Configuration\Configuration::hasCliArgument()`                                                               | 10.4.0 | `PHPUnit\TextUI\Configuration\Configuration::hasCliArguments()`                |
|       | `PHPUnit\Framework\Constraint\Constraint::exporter()`                                                                        | 10.4.0 |                                                                                |
|       | `PHPUnit\TextUI\Configuration\Configuration::registerMockObjectsFromTestArgumentsRecursively()`                              | 10.5.3 |                                                                                |
|       | `Test\AssertionFailed` and `Test\AssertionSucceeded` events                                                                  | 10.5.3 |                                                                                |
|       | `PHPUnit\Runner\Extension\Facade::requireExportOfObjects()` and `PHPUnit\Runner\Extension\Facade::requiresExportOfObjects()` | 10.5.3 |                                                                                |

## Hard Deprecations

This functionality is currently [hard-deprecated](https://phpunit.de/backward-compatibility.html#hard-deprecation):

### Writing Tests

#### Miscellaneous

| Issue                                                             | Description                                                                                                                                               | Since   | Replacement |
|-------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------|---------|-------------|
| [#5100](https://github.com/sebastianbergmann/phpunit/issues/5100) | Support for non-static data provider methods, non-public data provider methods, and data provider methods that declare parameters                         | 10.0.0  |             |
| [#5812](https://github.com/sebastianbergmann/phpunit/pull/5812)   | Support for string array keys in data sets returned by data provider methods that do not match the parameter names of the test method(s) that use(s) them | 10.5.18 |             |
