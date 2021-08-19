# HTTPlug

[![Latest Version](https://img.shields.io/github/release/php-http/httplug.svg?style=flat-square)](https://github.com/php-http/httplug/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Build Status](https://img.shields.io/travis/php-http/httplug/master.svg?style=flat-square)](https://travis-ci.org/php-http/httplug)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/php-http/httplug.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/httplug)
[![Quality Score](https://img.shields.io/scrutinizer/g/php-http/httplug.svg?style=flat-square)](https://scrutinizer-ci.com/g/php-http/httplug)
[![Total Downloads](https://img.shields.io/packagist/dt/php-http/httplug.svg?style=flat-square)](https://packagist.org/packages/php-http/httplug)

[![Slack Status](http://slack.httplug.io/badge.svg)](http://slack.httplug.io)
[![Email](https://img.shields.io/badge/email-team@httplug.io-blue.svg?style=flat-square)](mailto:team@httplug.io)

**HTTPlug, the HTTP client abstraction for PHP.**


## Intro

HTTP client standard built on [PSR-7](http://www.php-fig.org/psr/psr-7/) HTTP
messages. The HTTPlug client interface is compatible with the official standard
for the HTTP client interface, [PSR-18](http://www.php-fig.org/psr/psr-18/).
HTTPlug adds an interface for asynchronous HTTP requests, which PSR-18 does not
cover.

Since HTTPlug has already been widely adopted and a whole ecosystem has been
built around it, we will keep maintaining this package for the time being.
HTTPlug 2.0 and newer extend the PSR-18 interface to allow for a convenient
migration path.

New client implementations and consumers should use the PSR-18 interfaces
directly. In the long term, we expect PSR-18 to completely replace the need
for HTTPlug.


## History

HTTPlug is the official successor of the [ivory http adapter](https://github.com/egeloen/ivory-http-adapter).
HTTPlug is a predecessor of [PSR-18](http://www.php-fig.org/psr/psr-18/)


## Install

Via Composer

``` bash
$ composer require php-http/httplug
```


## Documentation

Please see the [official documentation](http://docs.php-http.org).


## Testing

``` bash
$ composer test
```


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
