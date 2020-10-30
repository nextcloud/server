# Installation

The latest version of Psalm requires PHP >= 7.3 and [Composer](https://getcomposer.org/).

```bash
composer require --dev vimeo/psalm
```

Generate a config file:

```bash
./vendor/bin/psalm --init
```

Psalm will scan your project and figure out an appropriate [error level](error_levels.md) for your codebase.

Then run Psalm:

```bash
./vendor/bin/psalm
```

Psalm will probably find a number of issues - find out how to deal with them in [Dealing with code issues](dealing_with_code_issues.md).

## Installing plugins

While Psalm can figure out the types used by various libraries based on the
their source code and docblocks, it works even better with custom-tailored types
provided by Psalm plugins.

Check out the list of existing plugins on Packagist: https://packagist.org/?type=psalm-plugin
Install them with `composer require --dev <plugin/package> && vendor/bin/psalm-plugin enable <plugin/package>`

Read more about plugins in [Using Plugins chapter](plugins/using_plugins.md).

## Using the Phar

Sometimes your project can conflict with one or more of Psalm’s dependencies.

In that case you may find the Phar (a self-contained PHP executable) useful.

Run `composer require --dev psalm/phar` to install it.
