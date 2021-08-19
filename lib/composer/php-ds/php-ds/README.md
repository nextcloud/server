# Data Structures for PHP 7

[![Build Status](https://travis-ci.org/php-ds/polyfill.svg?branch=master)](https://travis-ci.org/php-ds/polyfill)
[![Code Coverage](https://scrutinizer-ci.com/g/php-ds/polyfill/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/php-ds/polyfill/?branch=master)
[![Packagist](https://img.shields.io/packagist/v/php-ds/php-ds.svg)](https://packagist.org/packages/php-ds/php-ds)

This is a compatibility polyfill for the [extension](https://github.com/php-ds/extension). You should include this package as a dependency of your project
to ensure that your codebase would still be functional in an environment where the extension is not installed. The polyfill will not be loaded if the extension is installed and enabled.

## Install

```bash
composer require php-ds/php-ds
```

You can also *require* that the extension be installed using `ext-ds`. 

## Test

```
composer install
composer test
```

Make sure that the *ds* extension is not enabled, as the polyfill will not be loaded if it is. 
The test output will indicate whether the extension is active.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for more information.

### Credits

- [Rudi Theunissen](https://github.com/rtheunissen)
- [Joe Watkins](https://github.com/krakjoe)

### License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.
