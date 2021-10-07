# Composer bin plugin — Isolate your bin dependencies

[![Package version](http://img.shields.io/packagist/v/bamarni/composer-bin-plugin.svg?style=flat-square)](https://packagist.org/packages/bamarni/composer-bin-plugin)
[![Build Status](https://img.shields.io/travis/bamarni/composer-bin-plugin.svg?branch=master&style=flat-square)](https://travis-ci.org/bamarni/composer-bin-plugin?branch=master)
[![License](https://img.shields.io/badge/license-MIT-red.svg?style=flat-square)](LICENSE)


## Table of Contents

1. [Why?](#why)
1. [How does this plugin work?](#how-does-this-plugin-work)
1. [Installation](#installation)
1. [Usage](#usage)
    1. [Example](#example)
    1. [The `all` bin namespace](#the-all-bin-namespace)
    1. [What happens when symlink conflicts?](#what-happens-when-symlink-conflicts)
1. [Tips](#tips)
    1. [Auto-installation](#auto-installation)
    1. [Disable links](#disable-links)
    1. [Change directory](#change-directory)
    1. [Reduce clutter](#reduce-clutter)
1. [Related plugins](#related-plugins)


## Why?

In PHP, with Composer, your dependencies are flattened, which might result in conflicts. Most of the time those
conflicts are legitimate and should be properly resolved. However you may have dev tools that you want to manage
via Composer for convenience, but should not influence your project dependencies or for which conflicts don't make
sense. For example: [EtsyPhan][1] and [PhpMetrics][2]. Installing one of those static analysis tools should not change
your application dependencies, neither should it be a problem to install both of them at the same time.


## How does this plugin work?

It allows you to install your *bin vendors* in isolated locations, and still link them to your
[bin-dir][3] (if you want to).

This is done by registering a `bin` command, which can be used to run Composer commands inside a namespace.


## Installation

    # Globally
    $ composer global require bamarni/composer-bin-plugin

    # In your project
    $ composer require --dev bamarni/composer-bin-plugin


## Usage

    $ composer bin [namespace] [composer_command]
    $ composer global bin [namespace] [composer_command]


### Example

Let's install [Behat][4] and [PhpSpec][5] inside a `bdd` bin namespace, [EtsyPhan][1] in `etsy-phan` and [PhpMetrics][2]
in `phpmetrics`:

    $ composer bin bdd require behat/behat phpspec/phpspec
    $ composer bin etsy-phan require etsy/phan
    $ composer bin phpmetrics require phpmetrics/phpmetrics

This command creates the following directory structure :

    .
    ├── composer.json
    ├── composer.lock
    ├── vendor/
    │   └── bin
    │       ├── behat -> ../../vendor-bin/bdd/vendor/behat/behat/bin/behat
    │       ├── phpspec -> ../../vendor-bin/bdd/vendor/phpspec/phpspec/bin/phpspec
    │       ├── phan -> ../../vendor-bin/etsy-phan/vendor/etsy/phan/phan
    │       └── phpmetrics -> ../../vendor-bin/phpmetrics/vendor/phpmetrics/phpmetrics/bin/phpmetrics
    └── vendor-bin/
        └── bdd
        │   ├── composer.json
        │   ├── composer.lock
        │   └── vendor/
        │       ├── behat/
        │       ├── phpspec/
        │       └── ...
        └── etsy-phan
        │   ├── composer.json
        │   ├── composer.lock
        │   └── vendor/
        │       ├── etsy/
        │       └── ...
        └── phpmetrics
            ├── composer.json
            ├── composer.lock
            └── vendor/
                ├── phpmetrics/
                └── ...


You can continue to run `vendor/bin/behat`, `vendor/bin/phpspec` and co. as before but they will be properly isolated.
Also, `composer.json` and `composer.lock` files in each namespace will allow you to take advantage of automated dependency 
management as normally provided by Composer.

### The `all` bin namespace

The `all` bin namespace has a special meaning. It runs a command for all existing bin namespaces. For instance, the
following command would update all your bins :

    $ composer bin all update
    Changed current directory to vendor-bin/phpspec
    Loading composer repositories with package information
    Updating dependencies (including require-dev)
    Nothing to install or update
    Generating autoload files
    Changed current directory to vendor-bin/phpunit
    Loading composer repositories with package information
    Updating dependencies (including require-dev)
    Nothing to install or update
    Generating autoload files


### What happens when symlink conflicts?

If we take the case described in the [example section](#example), there might be more binaries linked due to
the dependencies. For example [PhpMetrics][2] depends on [Nikic PHP-Parser][6] and as such you will also have `php-parse`
in `.vendor/bin/`:

    .
    ├── composer.json
    ├── composer.lock
    ├── vendor/
    │   └── bin
    │       ├── phpmetrics -> ../../vendor-bin/phpmetrics/vendor/phpmetrics/phpmetrics/bin/phpmetrics
    │       └── php-parse -> ../../vendor-bin/phpmetrics/vendor/nikic/PHP-Parser/bin/php-parsee
    └── vendor-bin/
        └── phpmetrics
            ├── composer.json
            ├── composer.lock
            └── vendor/
                ├── phpmetrics/
                ├── nikic/
                └── ...

But what happens if another bin-namespace has a dependency using [Nikic PHP-Parser][6]? In that situation symlinks would
collides and are not created (only the colliding ones).


## Tips

### Auto-installation

For convenience, you can add the following script in your `composer.json` :

```json
{
    "scripts": {
        "bin": "echo 'bin not installed'",
        "post-install-cmd": ["@composer bin all install --ansi"],
        "post-update-cmd": ["@composer bin all update --ansi"]
    }
}
```

This makes sure all your bins are installed during `composer install` and updated during `composer update`.


### Disable links

By default, binaries of the sub namespaces are linked to the root one like described in [example](#example). If you
wish to disable that behaviour, you can do so by adding a little setting in the extra config:

```json
{
    "extra": {
        "bamarni-bin": {
            "bin-links": false
        }
    }
}
```


### Change directory

By default, the packages are looked for in the `vendor-bin` directory. The location can be changed using `target-directory` value in the extra config:

```json
{
    "extra": {
        "bamarni-bin": {
            "target-directory": "ci/vendor"
        }
    }
}
```

### Reduce clutter

You can add following line to your `.gitignore` file in order to avoid committing dependencies of your tools.

```.gitignore
/vendor-bin/**/vendor
```

Updating each tool can create many not legible changes in `composer.lock` files. You can use `.gitattributes` file in order 
to inform git that it shouldn't show diffs of `composer.lock` files.

```.gitattributes
vendor-bin/**/composer.lock binary
```

## Related plugins

* [theofidry/composer-inheritance-plugin][7]: Opinionated version of [Wikimedia composer-merge-plugin][8] to work in pair with this plugin.


[1]: https://github.com/etsy/phan
[2]: https://github.com/phpmetrics/PhpMetrics
[3]: https://getcomposer.org/doc/06-config.md#bin-dir
[4]: http://behat.org
[5]: http://phpspec.net
[6]: https://github.com/nikic/PHP-Parser
[7]: https://github.com/theofidry/composer-inheritance-plugin
[8]: https://github.com/wikimedia/composer-merge-plugin
