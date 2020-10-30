# Using Plugins

Psalm can be extended through plugins to find and fix domain-specific issues.

## Using Composer-based plugins

Psalm plugins are distributed as composer packages.

### Discovering plugins

You can find a list of plugins on [Psalm’s own website](https://psalm.dev/plugins), and [also on Packagist](https://packagist.org/?type=psalm-plugin). Alternatively you can get a list via the CLI by typing `composer search -t psalm-plugin '.'`

### Installing plugins

`composer require --dev <plugin-vendor/plugin-package>`

### Managing known plugins

Once installed, use the `psalm-plugin` tool to enable, disable and show available and enabled plugins.

To enable a plugin, run `vendor/bin/psalm-plugin enable plugin-vendor/plugin-package`.

To disable a plugin, run `vendor/bin/psalm-plugin disable plugin-vendor/plugin-package`.

`vendor/bin/psalm-plugin show` will show you a list of all local plugins (enabled and disabled).

## Using your own plugins

Is there no plugin for your favourite framework / library yet? Create it! It's as easy as forking a repository, tweaking some docblocks and publishing the package to Packagist.

Consult [Authoring Plugins](authoring_plugins.md) chapter to get started.
