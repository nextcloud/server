# Composer bin plugin — Isolate your bin dependencies

[![Package version](http://img.shields.io/packagist/v/bamarni/composer-bin-plugin.svg?style=flat-square)](https://packagist.org/packages/bamarni/composer-bin-plugin)
[![License](https://img.shields.io/badge/license-MIT-red.svg?style=flat-square)](LICENSE)


## Table of Contents

1. [Why? A hard problem with a simple solution.](#why-a-hard-problem-with-a-simple-solution)
1. [Usage; How does this plugin work?](#usage-how-does-this-plugin-work)
1. [Installation](#installation)
1. [Configuration](#configuration)
   1. [Bin links (`bin-links`)](#bin-links-bin-links)
   1. [Target directory (`target-directory`)](#target-directory-target-directory)
   1. [Forward command (`forward-command`)](#forward-command-forward-command)
1. [Tips & Tricks](#tips--tricks)
    1. [Auto-installation](#auto-installation)
    1. [Reduce clutter](#reduce-clutter)
    1. [GitHub Actions integration](#github-actions-integration)
1. [Related plugins](#related-plugins)
1. [Backward Compatibility Promise](#backward-compatibility-promise)
1. [Contributing](#contributing)


## Why? A hard problem with a simple solution.

When managing your dependencies with [Composer][composer], your dependencies are
flattened with compatible versions, or when not possible, result in conflict
errors.

There is cases however when adding a tool as a dependency, for example [PHPStan][phpstan]*
or [Rector][rector] could have undesired effects due to the dependencies they
are bringing. For example if phpstan depends on `nikic/php-parser` 4.x and rector
3.x, you cannot install both tools at the same time (despite the fact that from
a usage perspective, they do not need to be compatible). Another example, maybe
you can no longer add a non-dev dependency because a dependency brought by PHPStan
is not compatible with it.

There is nothing special or exceptional about this problem: this is how dependencies
work in PHP with Composer. It is however annoying in the case highlighted above,
because the conflicts should not be: it is a limitation of Composer because it
cannot infer how you are using each dependency.

One way to solve the problem above, is to install those dependencies in a 
different `composer.json` file. It comes with its caveats, for example if you
were to do that with [PHPUnit][phpunit], you may find yourself in the scenario
where PHPUnit will not be able to execute your tests because your code is not
compatible with it and Composer is not able to tell since the PHPUnit dependency
sits alone in its own `composer.json`. It is the very problem Composer aim to
solve. As a rule of thumb, **you should limit this approach to tools which do not
autoload your code.**

However, managing several `composer.json` kind be a bit annoying. This plugin
aims at helping you doing this.


*: You will in practice not have this problem with PHPStan as the Composer package
`phpstan/phpstan` is shipping a scoped PHAR (scoped via [PHP-Scoper][php-scoper])
which provides not only a package with no dependencies but as well that has no
risk of conflicting/crash when autoloading your code.


## Usage; How does this plugin work?

This plugin registers a `bin <bin-namespace-name>` command that allows you to
interact with the `vendor-bin/<bin-namespace-name>/composer.json`* file.

For example:

```bash
$ composer bin php-cs-fixer require --dev friendsofphp/php-cs-fixer

# Equivalent to manually doing:
$ mkdir vendor-bin/php-cs-fixer
$ cd vendor-bin/php-cs-fixer && composer require --dev friendsofphp/php-cs-fixer
```

You also have a special `all` namespace to interact with all the bin namespaces:

```bash
# Runs "composer update" for each bin namespace
$ composer bin all update
```


## Installation

```bash
$ composer require --dev bamarni/composer-bin-plugin
```


## Configuration

```json
{
    ...
    "extra": {
        "bamarni-bin": {
            "bin-links": false,
            "target-directory": "vendor-bin",
            "forward-command": true
        }
    }
}
```


### Bin links (`bin-links`)

In 1.x: enabled by default.
In 2.x: disabled by default.

When installing a Composer package, Composer may add "bin links" to a bin
directory. For example, by default when installing `phpunit/phpunit`, it will
add a symlink `vendor/bin/phpunit` pointing to the PHPUnit script somewhere in
`vendor/phpunit/phpunit`.

In 1.x, BamarniBinPlugin behaves the same way for "bin dependencies", i.e. when
executing `composer bin php-cs-fixer require --dev friendsofphp/php-cs-fixer`,
it will add a bin link `vendor/bin/php-cs-fixer -> vendor-bin/php-cs-fixer/vendor/friendsofphp/php-cs-fixer`.

This is however a bit tricky and cannot provide consistent behaviour. For example
when installing several packages with the same bin, (e.g. with the case above installing
another tool that uses PHP-CS-Fixer as a dependency in another bin namespace),
the symlink may or may not be overridden, or not created at all. Since it is not
possible to control this behaviour, neither provide an intuitive or deterministic
approach, it is recommended to set this setting to `false` which will be the
default in 2.x.

It does mean that instead of using `vendor/bin/php-cs-fixer` you will have to
use `vendor-bin/php-cs-fixer/vendor/friendsofphp/php-cs-fixer/path/tophp-cs-fixer`
(in which case setting an alias via a Composer script or something is recommended).


### Target directory (`target-directory`)

Defaults to `vendor-bin`, can be overridden to anything you wish.


### Forward command (`forward-command`)

Disabled by default in 1.x, will be enabled by default in 2.x. If this mode is
enabled, all your `composer install` and `composer update` commands are forwarded
to _all_ bin directories.

This is a replacement for the tasks shown in section [Auto-installation](#auto-installation).


## Tips & Tricks

### Auto-installation

You can easily forward a command upon a `composer install` to forward the install
to all (in which case having `extra.bamarni-bin.forward_command = true` is more
adapted) or a specific of bin namespace:

```json
{
    "scripts": {
        "bin": "echo 'bin not installed'",
        "post-install-cmd": ["@composer bin php-cs-fixer install --ansi"]
    }
}
```

You can customise this as you wish leveraging the [Composer script events][composer-script-events]).


### Reduce clutter

You can add the following line to your `.gitignore` file in order to avoid
committing dependencies of your tools.

```.gitignore
# .gitignore
/vendor-bin/**/vendor/
```

Updating each tool can create many not legible changes in `composer.lock` files.
You can use a `.gitattributes` file in order to inform git that it shouldn't show
diffs of `composer.lock` files.

```.gitattributes
# .gitattributes
/vendor-bin/**/composer.lock binary
```

### GitHub Actions integration

There is currently no way to leverage `ramsey/composer-install` to install all
namespace bins. However it is unlikely you need this in the CI and not locally,
in which case [forwarding the command](#forward-command-forward-command) should
be good enough.

If you still need to install specific bin namespaces, you can do it by setting
the `working-directory`:

```yaml
#...
    -   name: "Install PHP-CS-Fixer Composer dependencies"
        uses: "ramsey/composer-install@v2"
        with:
            working-directory: "vendor-bin/php-cs-fixer"
```



## Related plugins

* [theofidry/composer-inheritance-plugin][theofidry-composer-inheritance-plugin]: Opinionated version
  of [Wikimedia composer-merge-plugin][wikimedia-composer-merge-plugin] to work in pair with this plugin.


## Backward Compatibility Promise

The backward compatibility promise only applies to the following API:

- The commands registered by the plugin
- The behaviour of the commands (but not their logging/output)
- The Composer configuration

The plugin implementation is considered to be strictly internal and its code may
change at any time in a non back-ward compatible way.


## Contributing

A makefile is available to help out:

```bash
$ make # Runs all checks
$ make help # List all available commands
```

**Note:** you do need to install [phive][phive] first.


[composer]: https://getcomposer.org
[composer-script-events]: https://getcomposer.org/doc/articles/scripts.md#command-events
[phive]: https://phar.io/
[php-scoper]: https://github.com/humbug/php-scoper
[phpstan]: https://phpstan.org/
[phpunit]: https://github.com/sebastianbergmann/phpunit
[rector]: https://github.com/rectorphp/rector
[symfony-bc-policy]: https://symfony.com/doc/current/contributing/code/bc.html
[theofidry-composer-inheritance-plugin]: https://github.com/theofidry/composer-inheritance-plugin
[wikimedia-composer-merge-plugin]: https://github.com/wikimedia/composer-merge-plugin
