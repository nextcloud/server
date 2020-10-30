# Authoring Plugins

## Quick start

### Using a template repository

Head over to [plugin template repository](https://github.com/weirdan/psalm-plugin-skeleton) on Github and click `Use this template` button.

### Using skeleton project

Run `composer create-project weirdan/psalm-plugin-skeleton:dev-master your-plugin-name` to quickly bootstrap a new plugin project in `your-plugin-name` folder. Make sure you adjust namespaces in `composer.json`, `Plugin.php` and `tests` folder.


## Stub files

Stub files provide a way to override third-party type information when you cannot add Psalm's extended docblocks to the upstream source files directly.
By convention, stub files have `.phpstub` extension to avoid IDEs treating them as actual php code.

## Generating stubs

Dev-require the library you want to tweak types for, e.g.
```
composer require --dev cakephp/chronos
```
Then generate the stubs
```
vendor/bin/psalm --generate-stubs=stubs/chronos.phpstub
```
Open the generated file and remove everything not related to the library you're stubbing. Tweak the docblocks to provide more accurate types.

## Registering stub files

Skeleton/template project includes the code to register all `.phpstub` files from the `stubs` directory.

To register a stub file manually use `Psalm\Plugin\RegistrationInterface::addStubFile()`.

## Publishing your plugin on Packagist

Follow instructions on packagist.org under 'Publishing Packages' section.

## Advanced topics

### Starting from scratch

Composer-based plugin is a composer package which conforms to these requirements:

1. Its `type` field is set to `psalm-plugin`
2. It has `extra.psalm.pluginClass` subkey in its `composer.json` that reference an entry-point class that will be invoked to register the plugin into Psalm runtime.
3. Entry-point class implements `Psalm\Plugin\PluginEntryPointInterface`

### Psalm API

Plugins may implement one of (or more than one of) `Psalm\Plugin\Hook\*` interface(s).

```php
<?php
class SomePlugin implements \Psalm\Plugin\Hook\AfterStatementAnalysisInterface
{
}
```

`Psalm\Plugin\Hook\*` offers the following interfaces that you can implement:

- `AfterAnalysisInterface` - called after Psalm has completed its analysis. Use this hook if you want to do something with the analysis results.
- `AfterClassLikeAnalysisInterface` - called after Psalm has completed its analysis of a given class.
- `AfterClassLikeExistenceCheckInterface` - called after Psalm analyzes a reference to a class, interface or trait.
- `AfterClassLikeVisitInterface` - called after Psalm crawls the parsed Abstract Syntax Tree for a class-like (class, interface, trait). Due to caching the AST is crawled the first time Psalm sees the file, and is only re-crawled if the file changes, the cache is cleared, or you're disabling cache with `--no-cache`/`--no-reflection-cache`. Use this if you want to collect or modify information about a class before Psalm begins its analysis.
- `AfterCodebasePopulatedInterface` - called after Psalm has scanned necessary files and populated codebase data.
- `AfterEveryFunctionCallAnalysisInterface` - called after Psalm evaluates any function call. Cannot influence the call further.
- `AfterExpressionAnalysisInterface` - called after Psalm evaluates an expression.
- `AfterFileAnalyisisInterface` - called after Psalm analyzes a file.
- `AfterFunctionCallAnalysisInterface` - called after Psalm evaluates a function call to any function defined within the project itself. Can alter the return type or perform modifications of the call.
- `AfterFunctionLikeAnalysisInterface` - called after Psalm has completed its analysis of a given function-like.
- `AfterMethodCallAnalysisInterface` - called after Psalm analyzes a method call.
- `AfterStatementAnalysisInterface` - called after Psalm evaluates an statement.
- `BeforeFileAnalysisInterface` - called before Psalm analyzes a file.
- `FunctionExistenceProviderInterface` - can be used to override Psalm's builtin function existence checks for one or more functions.
- `FunctionParamsProviderInterface.php` - can be used to override Psalm's builtin function parameter lookup for one or more functions.
- `FunctionReturnTypeProviderInterface` - can be used to override Psalm's builtin function return type lookup for one or more functions.
- `MethodExistenceProviderInterface` - can be used to override Psalm's builtin method existence checks for one or more classes.
- `MethodParamsProviderInterface` - can be used to override Psalm's builtin method parameter lookup for one or more classes.
- `MethodReturnTypeProviderInterface` - can be used to override Psalm's builtin method return type lookup for one or more classes.
- `MethodVisibilityProviderInterface` - can be used to override Psalm's builtin method visibility checks for one or more classes.
- `PropertyExistenceProviderInterface` - can be used to override Psalm's builtin property existence checks for one or more classes.
- `PropertyTypeProviderInterface` - can be used to override Psalm's builtin property type lookup for one or more classes.
- `PropertyVisibilityProviderInterface` - can be used to override Psalm's builtin property visibility checks for one or more classes.

Here are a couple of example plugins:
 - [StringChecker](https://github.com/vimeo/psalm/blob/master/examples/plugins/StringChecker.php) - checks class references in strings
 - [PreventFloatAssignmentChecker](https://github.com/vimeo/psalm/blob/master/examples/plugins/PreventFloatAssignmentChecker.php) - prevents assignment to floats
 - [FunctionCasingChecker](https://github.com/vimeo/psalm/blob/master/examples/plugins/FunctionCasingChecker.php) - checks that your functions and methods are correctly-cased

To ensure your plugin runs when Psalm does, add it to your [config](../configuration.md) (not needed for composer-based plugins):
```xml
    <plugins>
        <plugin filename="src/plugins/SomePlugin.php" />
    </plugins>
```

You can also specify an absolute path to your plugin:
```xml
    <plugins>
        <plugin filename="/path/to/SomePlugin.php" />
    </plugins>
```

### Using Xdebug

As Psalm disables _Xdebug_ at runtime, if you need to debug your code step-by-step when authoring a plugin, you can allow the extension by running Psalm as following:

```console
$ PSALM_ALLOW_XDEBUG=1 path/to/psalm
```

## Type system

Understand how Psalm handles types by [reading this guide](plugins_type_system.md).

## Handling custom plugin issues

Plugins may sometimes need to emit their own issues (i.e. not emit one of the [existing issues](../issues.md)). If this is the case, they can emit an issue that extends `Psalm\Issue\PluginIssue`.

To suppress a custom plugin issue in docblocks you can just use its issue name (e.g. `/** @psalm-suppress NoFloatAssignment */`, but to [suppress it in Psalm’s config](../dealing_with_code_issues.md#config-suppression) you must use the pattern:

```xml
<PluginIssue name="NoFloatAssignment" errorLevel="suppress" />
```

You can also use more complex rules in the `<issueHandler />` element, as you can with any other issue type e.g.

```xml
<PluginIssue name="NoFloatAssignment">
    <errorLevel type="suppress">
        <directory name="tests" />
    </errorLevel>
</PluginIssue>
```

## Upgrading file-based plugin to composer-based version

Create new plugin project using skeleton, then pass the class name of you file-based plugin to `registerHooksFromClass()` method of the `Psalm\Plugin\RegistrationInterface` instance that was passed into your plugin entry point's `__invoke()` method. See the [conversion example](https://github.com/vimeo/psalm/tree/master/examples/plugins/composer-based/echo-checker/).
