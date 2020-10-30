# Configuration

Psalm uses an XML config file (by default, `psalm.xml`). A barebones example looks like this:

```xml
<?xml version="1.0"?>
<psalm>
    <projectFiles>
        <directory name="src" />
    </projectFiles>
</psalm>
```

Configuration file may be split into several files using [XInclude](https://www.w3.org/TR/xinclude/) tags (c.f. previous example):
#### psalm.xml
```xml
<?xml version="1.0"?>
<psalm
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
    xmlns:xi="http://www.w3.org/2001/XInclude"
>
    <xi:include href="files.xml"/>
</psalm>
```
#### files.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>
<projectFiles xmlns="https://getpsalm.org/schema/config">
    <file name="Bar.php" />
    <file name="Bat.php" />
</projectFiles>
```


## Optional &lt;psalm /&gt; attributes

### Coding style

#### errorLevel

```xml
<psalm
  errorLevel="[int]"
/>
```
This corresponds to Psalm‘s [error-detection level](error_levels.md).

#### reportMixedIssues

```xml
<psalm
  reportMixedIssues="[bool]"
/>
```
Setting this to `"false"` hides all issues with `Mixed` types in Psalm’s output. If not given, this defaults to `"false"` when `errorLevel` is 3 or higher, and `"true"` when the error level is 1 or 2.

#### totallyTyped

```xml
<psalm
  totallyTyped="[bool]"
/>
```

\(Deprecated\) Setting `totallyTyped` to `"true"` is equivalent to setting `errorLevel` to `"1"`. Setting `totallyTyped` to `"false"` is equivalent to setting `errorLevel` to `"2"` and `reportMixedIssues` to `"false"`



#### resolveFromConfigFile

```xml
<psalm
  resolveFromConfigFile="[bool]"
/>
```
If this is enabled, relative directories mentioned in the config file will be resolved relative to the location
of the config file. If it is disabled, or absent they will be resolved relative to the working directory of the Psalm process.

New versions of Psalm enable this option when generating config files. Older versions did not include it.

#### useDocblockTypes

```xml
<psalm
  useDocblockTypes="[bool]"
>
```
Whether or not to use types as defined in docblocks. Defaults to `true`.

#### useDocblockPropertyTypes

```xml
<psalm
  useDocblockPropertyTypes="[bool]"
>
```
If not using all docblock types, you can still use docblock property types. Defaults to `false` (though only relevant if `useDocblockTypes` is `false`.

#### usePhpDocMethodsWithoutMagicCall

```xml
<psalm
  usePhpDocMethodsWithoutMagicCall="[bool]"
>
```
The PHPDoc `@method` annotation normally only applies to classes with a `__call` method. Setting this to `true` allows you to use the `@method` annotation to override inherited method return types. Defaults to `false`.

#### usePhpDocPropertiesWithoutMagicCall

```xml
<psalm
  usePhpDocPropertiesWithoutMagicCall="[bool]"
>
```
The PHPDoc `@property`, `@property-read` and `@property-write` annotations normally only apply to classes with `__get`/`__set` methods. Setting this to `true` allows you to use the `@property`, `@property-read` and `@property-write` annotations to override property existence checks and resulting property types. Defaults to `false`.

#### strictBinaryOperands

```xml
<psalm
  strictBinaryOperands="[bool]"
>
```
If true we force strict typing on numerical and string operations (see https://github.com/vimeo/psalm/issues/24). Defaults to `false`.

#### rememberPropertyAssignmentsAfterCall

```xml
<psalm
  rememberPropertyAssignmentsAfterCall="[bool]"
>
```
Setting this to `false` means that any function calls will cause Psalm to forget anything it knew about object properties within the scope of the function it's currently analysing. This duplicates functionality that Hack has. Defaults to `true`.

#### allowPhpStormGenerics

```xml
<psalm
  allowPhpStormGenerics="[bool]"
>
```
Allows you to specify whether or not to use the typed iterator docblock format supported by PHP Storm e.g. `ArrayIterator|string[]`, which Psalm transforms to `ArrayIterator<string>`. Defaults to `false`.

#### allowStringToStandInForClass

```xml
<psalm
  allowStringToStandInForClass="[bool]"
>
```
When `true`, strings can be used as classes, meaning `$some_string::someMethod()` is allowed. If `false`, only class constant strings (of the form `Foo\Bar::class`) can stand in for classes, otherwise an `InvalidStringClass` issue is emitted. Defaults to `false`.

#### memoizeMethodCallResults

```xml
<psalm
  memoizeMethodCallResults="[bool]"
>
```
When `true`, the results of method calls without arguments passed arguments are remembered between repeated calls of that method on a given object. Defaults to `false`.

#### hoistConstants

```xml
<psalm
  hoistConstants="[bool]"
>
```
When `true`, constants defined in a function in a file are assumed to be available when requiring that file, and not just when calling that function. Defaults to `false` (i.e. constants defined in functions will *only* be available for use when that function is called)

#### addParamDefaultToDocblockType

```xml
<psalm
  addParamDefaultToDocblockType="[bool]"
>
```
Occasionally a param default will not match up with the docblock type. By default, Psalm emits an issue. Setting this flag to `true` causes it to expand the param type to include the param default. Defaults to `false`.

#### checkForThrowsDocblock
```xml
<psalm
  checkForThrowsDocblock="[bool]"
>
```
When `true`, Psalm will check that the developer has supplied `@throws` docblocks for every exception thrown in a given function or method. Defaults to `false`.

#### checkForThrowsInGlobalScope
```xml
<psalm
  checkForThrowsInGlobalScope="[bool]"
>
```
When `true`, Psalm will check that the developer has caught every exception in global scope. Defaults to `false`.

#### ignoreInternalFunctionFalseReturn

```xml
<psalm
  ignoreInternalFunctionFalseReturn="[bool]"
>
```
When `true`, Psalm ignores possibly-false issues stemming from return values of internal functions (like `preg_split`) that may return false, but do so rarely. Defaults to `true`.

#### ignoreInternalFunctionNullReturn

```xml
<psalm
  ignoreInternalFunctionNullReturn="[bool]"
>
```
When `true`, Psalm ignores possibly-null issues stemming from return values of internal array functions (like `current`) that may return null, but do so rarely. Defaults to `true`.

#### findUnusedVariablesAndParams
```xml
<psalm
  findUnusedVariablesAndParams="[bool]"
>
```
When `true`, Psalm will attempt to find all unused variables, the equivalent of running with `--find-unused-variables`. Defaults to `false`.

#### findUnusedCode
```xml
<psalm
  findUnusedCode="[bool]"
>
```
When `true`, Psalm will attempt to find all unused code (including unused variables), the equivalent of running with `--find-unused-code`. Defaults to `false`.

#### findUnusedPsalmSuppress
```xml
<psalm
  findUnusedPsalmSuppress="[bool]"
>
```
When `true`, Psalm will report all `@psalm-suppress` annotations that aren't used, the equivalent of running with `--find-unused-psalm-suppress`. Defaults to `false`.

#### loadXdebugStub
```xml
<psalm
  loadXdebugStub="[bool]"
>
```
If not present, Psalm will only load the Xdebug stub if Psalm has unloaded the extension.
When `true`, Psalm will load the Xdebug extension stub (as the extension is unloaded when Psalm runs).
Setting to `false` prevents the stub from loading.

#### ensureArrayStringOffsetsExist
```xml
<psalm
  ensureArrayStringOffsetsExist="[bool]"
>
```
When `true`, Psalm will complain when referencing an explicit string offset on an array e.g. `$arr['foo']` without a user first asserting that it exists (either via an `isset` check or via an object-like array). Defaults to `false`.

#### ensureArrayIntOffsetsExist
```xml
<psalm
  ensureArrayIntOffsetsExist="[bool]"
>
```
When `true`, Psalm will complain when referencing an explicit integer offset on an array e.g. `$arr[7]` without a user first asserting that it exists (either via an `isset` check or via an object-like array). Defaults to `false`.

#### phpVersion
```xml
<psalm
  phpVersion="[string]"
>
```
Set the php version Psalm should assume when checking and/or fixing the project. If this attribute is not set, Psalm uses the declaration in `composer.json` if one is present. It will check against the earliest version of PHP that satisfies the declared `php` dependency

This can be overridden on the command-line using the `--php-version=` flag which takes the highest precedence over both the `phpVersion` setting and the version derived from `composer.json`.

#### skipChecksOnUnresolvableIncludes
```xml
<psalm
  skipChecksOnUnresolvableIncludes="[bool]"
>
```

When `true`, Psalm will skip checking classes, variables and functions after it comes across an `include` or `require` it cannot resolve. This allows code to reference functions and classes unknown to Psalm.

This defaults to `false`.

#### sealAllMethods

```xml
<psalm
  sealAllMethods="[bool]"
>
```

When `true`, Psalm will treat all classes as if they had sealed methods, meaning that if you implement the magic method `__call`, you also have to add `@method` for each magic method. Defaults to false.

#### runTaintAnalysis

```xml
<psalm
  runTaintAnalysis="[bool]"
>
```

When `true`, Psalm will run [Taint Analysis](../security_analysis/index.md) on your codebase. This config is the same as if you were running Psalm with `--taint-analysis`.

#### reportInfo

```xml
<psalm
  reportInfo="[bool]"
>
```

When `false`, Psalm will not consider issue at lower level than `errorLevel` as `info` (they will be suppressed instead). This can be a big improvement in analysis time for big projects. However, this config will prevent Psalm to count or suggest fixes for suppressed issue

### Running Psalm

#### autoloader
```xml
<psalm
  autoloader="[string]"
>
```
If your application registers one or more custom autoloaders, and/or declares universal constants/functions, this autoloader script will be executed by Psalm before scanning starts. Psalm always registers composer's autoloader by default.

#### throwExceptionOnError
```xml
<psalm
  throwExceptionOnError="[bool]"
>
```
Useful in testing, this makes Psalm throw a regular-old exception when it encounters an error. Defaults to `false`.

#### hideExternalErrors
```xml
<psalm
  hideExternalErrors="[bool]"
>
```
Whether or not to show issues in files that are used by your project files, but which are not included in `<projectFiles>`. Defaults to `false`.

#### cacheDirectory
```xml
<psalm
  cacheDirectory="[string]"
>
```
The directory used to store Psalm's cache data - if you specify one (and it does not already exist), its parent directory must already exist, otherwise Psalm will throw an error.

Defaults to `$XDG_CACHE_HOME/psalm`. If `$XDG_CACHE_HOME` is either not set or empty, a default equal to `$HOME/.cache/psalm` is used or `sys_get_temp_dir() . '/psalm'` when not defined.

#### allowFileIncludes
```xml
<psalm
  allowFileIncludes="[bool]"
>
```
Whether or not to allow `require`/`include` calls in your PHP. Defaults to `true`.

#### serializer
```xml
<psalm
  serializer="['igbinary'|'default']"
>
```
Allows you to hard-code a serializer for Psalm to use when caching data. By default, Psalm uses `ext-igbinary` *if* the version is greater than or equal to 2.0.5, otherwise it defaults to PHP's built-in serializer.


## Project settings

#### &lt;projectFiles&gt;
Contains a list of all the directories that Psalm should inspect. You can also specify a set of files and folders to ignore with the `<ignoreFiles>` directive, e.g.
```xml
<projectFiles>
  <directory name="src" />
  <ignoreFiles>
    <directory name="src/Stubs" />
  </ignoreFiles>
</projectFiles>
```

#### &lt;extraFiles&gt;
Optional. Same format as `<projectFiles>`. Directories Psalm should load but not inspect.

#### &lt;fileExtensions&gt;
Optional.  A list of extensions to search over. See [Checking non-PHP files](checking_non_php_files.md) to understand how to extend this.

#### &lt;plugins&gt;
Optional.  A list of `<plugin filename="path_to_plugin.php" />` entries. See the [Plugins](plugins/using_plugins.md) section for more information.

#### &lt;issueHandlers&gt;
Optional.  If you don't want Psalm to complain about every single issue it finds, the issueHandler tag allows you to configure that. [Dealing with code issues](dealing_with_code_issues.md) tells you more.

#### &lt;mockClasses&gt;
Optional. Do you use mock classes in your tests? If you want Psalm to ignore them when checking files, include a fully-qualified path to the class with `<class name="Your\Namespace\ClassName" />`

#### &lt;universalObjectCrates&gt;
Optional. Do you have objects with properties that cannot be determined statically? If you want Psalm to treat all properties on a given classlike as mixed, include a fully-qualified path to the class with `<class name="Your\Namespace\ClassName" />`. By default, `stdClass` and `SimpleXMLElement` are configured to be universal object crates.

#### &lt;stubs&gt;
Optional. If your codebase uses classes and functions that are not visible to Psalm via reflection (e.g. if there are internal packages that your codebase relies on that are not available on the machine running Psalm), you can use stub files. Used by PhpStorm (a popular IDE) and others, stubs provide a description of classes and functions without the implementations. You can find a list of stubs for common classes [here](https://github.com/JetBrains/phpstorm-stubs). List out each file with `<file name="path/to/file.php" />`.

#### &lt;ignoreExceptions&gt;
Optional.  A list of exceptions to not report for `checkForThrowsDocblock` or `checkForThrowsInGlobalScope`. If an exception has `onlyGlobalScope` set to `true`, only `checkForThrowsInGlobalScope` is ignored for that exception, e.g.
```xml
<ignoreExceptions>
  <class name="fully\qualified\path\Exc" onlyGlobalScope="true" />
</ignoreExceptions>
```

#### &lt;globals&gt;
Optional.  If your codebase uses global variables that are accessed with the `global` keyword, you can declare their type.  e.g.
```xml
<globals>
  <var name="globalVariableName" type="type" />
</globals>
```
