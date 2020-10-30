Webmozart Glob
==============

[![Build Status](https://travis-ci.org/webmozart/glob.svg?branch=4.1.0)](https://travis-ci.org/webmozart/glob)
[![Build status](https://ci.appveyor.com/api/projects/status/das6j3x0org6219m/branch/master?svg=true)](https://ci.appveyor.com/project/webmozart/glob/branch/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/webmozart/glob/badges/quality-score.png?b=4.1.0)](https://scrutinizer-ci.com/g/webmozart/glob/?branch=4.1.0)
[![Latest Stable Version](https://poser.pugx.org/webmozart/glob/v/stable.svg)](https://packagist.org/packages/webmozart/glob)
[![Total Downloads](https://poser.pugx.org/webmozart/glob/downloads.svg)](https://packagist.org/packages/webmozart/glob)
[![Dependency Status](https://www.versioneye.com/php/webmozart:glob/4.1.0/badge.svg)](https://www.versioneye.com/php/webmozart:glob/4.1.0)

Latest release: [4.1.0](https://packagist.org/packages/webmozart/glob#4.1.0)

A utility implementing Ant-like globbing. 

Syntax:

* `?` matches any character
* `*` matches zero or more characters, except `/`
* `/**/` matches zero or more directory names
* `[abc]` matches a single character `a`, `b` or `c`
* `[a-c]` matches a single character `a`, `b` or `c`
* `[^abc]` matches any character but `a`, `b` or `c`
* `[^a-c]` matches any character but `a`, `b` or `c`
* `{ab,cd}` matches `ab` or `cd`

[API Documentation]

Comparison with glob()
----------------------

Compared to PHP's native `glob()` function, this utility supports:

* `/**/` for matching zero or more directories
* globbing custom stream wrappers, like `myscheme://path/**/*.css`
* matching globs against path strings
* filtering arrays of path strings by a glob
* exceptions if the glob contains invalid syntax

Since PHP's native `glob()` function is much more efficient, this utility uses 
`glob()` internally whenever possible (i.e. when no special feature is used).

Installation
------------

Use [Composer] to install the package:

```
$ composer require webmozart/glob
```

Usage
-----

The main class of the package is [`Glob`]. Use `Glob::glob()` to glob the 
filesystem:

```php
use Webmozart\Glob\Glob;

$paths = Glob::glob('/path/to/dir/*.css'); 
```

You can also use [`GlobIterator`] to search the filesystem iteratively. However,
the iterator is not guaranteed to return sorted results:

```php
use Webmozart\Glob\Iterator\GlobIterator;

$iterator = new GlobIterator('/path/to/dir/*.css');

foreach ($iterator as $path) {
    // ...
}
```

### Path Matching

The package also provides utility methods for comparing paths against globs.
Use `Glob::match()` to match a path against a glob:

```php
if (Glob::match($path, '/path/to/dir/*.css')) {
    // ...
}
```

`Glob::filter()` filters a list of paths by a glob:

```php
$paths = Glob::filter($paths, '/path/to/dir/*.css');
```

The same can be achieved iteratively with [`GlobFilterIterator`]:

```php
use Webmozart\Glob\Iterator\GlobFilterIterator;

$iterator = new GlobFilterIterator('/path/to/dir/*.css', new ArrayIterator($paths));

foreach ($iterator as $path) {
    // ...
}
```

You can also filter the keys of the path list by passing the `FILTER_KEY`
constant of the respective class:


```php
$paths = Glob::filter($paths, '/path/to/dir/*.css', Glob::FILTER_KEY);

$iterator = new GlobFilterIterator(
    '/path/to/dir/*.css', 
    new ArrayIterator($paths),
    GlobFilterIterator::FILTER_KEY
);
```

### Relative Globs

Relative globs such as `*.css` are not supported. Usually, such globs refer to
paths relative to the current working directory. This utility, however, does not
want to make such assumptions. Hence you should always pass absolute globs.

If you want to allow users to pass relative globs, I recommend to turn the globs
into absolute globs using the [Webmozart Path Utility]:

```php
use Webmozart\Glob\Glob;
use Webmozart\PathUtil\Path;

// If $glob is absolute, that glob is used without modification.
// If $glob is relative, it is turned into an absolute path based on the current
// working directory.
$paths = Glob::glob(Path::makeAbsolute($glob, getcwd());
```

### Windows Compatibility

Globs need to be passed in [canonical form] with forward slashes only.
Returned paths contain forward slashes only.

### Escaping

The `Glob` class supports escaping by typing a backslash character `\` before
any special character:

```php
$paths = Glob::glob('/backup\\*/*.css');
```

In this example, the glob matches all CSS files in the `/backup*` directory 
rather than in all directories starting with `/backup`. Due to PHP's own 
escaping in strings, the backslash character `\` needs to be typed twice to 
produce a single `\` in the string.

The following escape sequences are available:

* `\\?`: match a `?` in the path
* `\\*`: match a `*` in the path
* `\\{`: match a `{` in the path
* `\\}`: match a `}` in the path
* `\\[`: match a `[` in the path
* `\\]`: match a `]` in the path
* `\\^`: match a `^` in the path
* `\\-`: match a `-` in the path
* `\\\\`: match a `\` in the path

### Stream Wrappers

The `Glob` class supports [stream wrappers]:

```php
$paths = Glob::glob('myscheme:///**/*.css');
```

Authors
-------

* [Bernhard Schussek] a.k.a. [@webmozart]
* [The Community Contributors]

Contribute
----------

Contributions to the package are always welcome!

* Report any bugs or issues you find on the [issue tracker].
* You can grab the source code at the package's [Git repository].

Support
-------

If you are having problems, send a mail to bschussek@gmail.com or shout out to
[@webmozart] on Twitter.

License
-------

All contents of this package are licensed under the [MIT license].

[API Documentation]: https://webmozart.github.io/glob/api/latest
[Composer]: https://getcomposer.org
[Bernhard Schussek]: http://webmozarts.com
[The Community Contributors]: https://github.com/webmozart/glob/graphs/contributors
[issue tracker]: https://github.com/webmozart/glob/issues
[Git repository]: https://github.com/webmozart/glob
[@webmozart]: https://twitter.com/webmozart
[MIT license]: LICENSE
[Webmozart Path Utility]: https://github.com/webmozart/path-util
[canonical form]: https://webmozart.github.io/path-util/api/latest/class-Webmozart.PathUtil.Path.html#_canonicalize
[stream wrappers]: http://php.net/manual/en/wrappers.php
[`Glob`]: https://webmozart.github.io/glob/api/latest/class-Webmozart.Glob.Glob.html
[`GlobIterator`]: https://webmozart.github.io/glob/api/latest/class-Webmozart.Glob.Iterator.GlobIterator.html
[`GlobFilterIterator`]: https://webmozart.github.io/glob/api/latest/class-Webmozart.Glob.Iterator.GlobFilterIterator.html
