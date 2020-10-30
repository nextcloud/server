File Path Utility
=================

[![Build Status](https://travis-ci.org/webmozart/path-util.svg?branch=2.3.0)](https://travis-ci.org/webmozart/path-util)
[![Build status](https://ci.appveyor.com/api/projects/status/d5uuypr6p162gpxf/branch/master?svg=true)](https://ci.appveyor.com/project/webmozart/path-util/branch/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/webmozart/path-util/badges/quality-score.png?b=2.3.0)](https://scrutinizer-ci.com/g/webmozart/path-util/?branch=2.3.0)
[![Latest Stable Version](https://poser.pugx.org/webmozart/path-util/v/stable.svg)](https://packagist.org/packages/webmozart/path-util)
[![Total Downloads](https://poser.pugx.org/webmozart/path-util/downloads.svg)](https://packagist.org/packages/webmozart/path-util)
[![Dependency Status](https://www.versioneye.com/php/webmozart:path-util/2.3.0/badge.svg)](https://www.versioneye.com/php/webmozart:path-util/2.3.0)

Latest release: [2.3.0](https://packagist.org/packages/webmozart/path-util#2.3.0)

PHP >= 5.3.3

This package provides robust, cross-platform utility functions for normalizing,
comparing and modifying file paths and URLs.

Installation
------------

The utility can be installed with [Composer]:

```
$ composer require webmozart/path-util
```

Usage
-----

Use the `Path` class to handle file paths:

```php
use Webmozart\PathUtil\Path;

echo Path::canonicalize('/var/www/vhost/webmozart/../config.ini');
// => /var/www/vhost/config.ini

echo Path::canonicalize('C:\Programs\Webmozart\..\config.ini');
// => C:/Programs/config.ini

echo Path::canonicalize('~/config.ini');
// => /home/webmozart/config.ini

echo Path::makeAbsolute('config/config.yml', '/var/www/project');
// => /var/www/project/config/config.yml

echo Path::makeRelative('/var/www/project/config/config.yml', '/var/www/project/uploads');
// => ../config/config.yml

$paths = array(
    '/var/www/vhosts/project/httpdocs/config/config.yml',
    '/var/www/vhosts/project/httpdocs/images/banana.gif',
    '/var/www/vhosts/project/httpdocs/uploads/../images/nicer-banana.gif',
);

Path::getLongestCommonBasePath($paths);
// => /var/www/vhosts/project/httpdocs

Path::getFilename('/views/index.html.twig');
// => index.html.twig

Path::getFilenameWithoutExtension('/views/index.html.twig');
// => index.html

Path::getFilenameWithoutExtension('/views/index.html.twig', 'html.twig');
Path::getFilenameWithoutExtension('/views/index.html.twig', '.html.twig');
// => index

Path::getExtension('/views/index.html.twig');
// => twig

Path::hasExtension('/views/index.html.twig');
// => true

Path::hasExtension('/views/index.html.twig', 'twig');
// => true

Path::hasExtension('/images/profile.jpg', array('jpg', 'png', 'gif'));
// => true

Path::changeExtension('/images/profile.jpeg', 'jpg');
// => /images/profile.jpg

Path::join('phar://C:/Documents', 'projects/my-project.phar', 'composer.json');
// => phar://C:/Documents/projects/my-project.phar/composer.json

Path::getHomeDirectory();
// => /home/webmozart
```

Use the `Url` class to handle URLs:

```php
use Webmozart\PathUtil\Url;

echo Url::makeRelative('http://example.com/css/style.css', 'http://example.com/puli');
// => ../css/style.css

echo Url::makeRelative('http://cdn.example.com/css/style.css', 'http://example.com/puli');
// => http://cdn.example.com/css/style.css
```

Learn more in the [Documentation] and the [API Docs].

Authors
-------

* [Bernhard Schussek] a.k.a. [@webmozart]
* [The Community Contributors]

Documentation
-------------

Read the [Documentation] if you want to learn more about the contained functions.

Contribute
----------

Contributions are always welcome!

* Report any bugs or issues you find on the [issue tracker].
* You can grab the source code at the [Git repository].

Support
-------

If you are having problems, send a mail to bschussek@gmail.com or shout out to
[@webmozart] on Twitter.

License
-------

All contents of this package are licensed under the [MIT license].

[Bernhard Schussek]: http://webmozarts.com
[The Community Contributors]: https://github.com/webmozart/path-util/graphs/contributors
[Composer]: https://getcomposer.org
[Documentation]: docs/usage.md
[API Docs]: https://webmozart.github.io/path-util/api/latest/class-Webmozart.PathUtil.Path.html
[issue tracker]: https://github.com/webmozart/path-util/issues
[Git repository]: https://github.com/webmozart/path-util
[@webmozart]: https://twitter.com/webmozart
[MIT license]: LICENSE
