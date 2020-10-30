# Nextcloud Coding Standard

Nextcloud coding standards for the [php cs fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer).

## Installation

Add the package to your dev dependencies

```bash
composer require --dev nextcloud/coding-standard
```

and create a `.php_cs.dist` like

```php
<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use Nextcloud\CodingStandard\Config;

$config = new Config();
$config
	->getFinder()
	->ignoreVCSIgnored(true)
	->notPath('build')
	->notPath('l10n')
	->notPath('src')
	->notPath('vendor')
	->in(__DIR__);
return $config;
```

To run the fixer you first have to [install it](https://github.com/FriendsOfPhp/PHP-CS-Fixer#installation). Then you can run `php-cs fix` to apply all automated fixes.

For convenience you may add it to the `scripts` section of your `composer.json`:

```json
{
    "scripts": {
        "cs:check": "php-cs-fixer fix --dry-run",
        "cs:fix": "php-cs-fixer fix"
    }
}
```
