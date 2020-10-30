Changelog
=========

* 4.1.0 (2015-12-29)

 * added flag `Glob::FILTER_VALUE` for `Glob::filter()`
 * added flag `Glob::FILTER_KEY` for `Glob::filter()`

* 4.0.0 (2015-12-28)

 * switched to a better-performing algorithm for `Glob::toRegEx()`
 * switched to a better-performing algorithm for `Glob::getStaticPrefix()`
 * removed `Glob::ESCAPE` flag - escaping is now always enabled
 * added argument `$delimiter` to `Glob::toRegEx()`
 * removed `Symbol` class

* 3.3.1 (2015-12-23)

 * checked return value of `glob()`

* 3.3.0 (2015-12-23)

 * improved globbing performance by falling back to PHP's `glob()` function
   whenever possible
 * added support for character ranges `[a-c]`

* 3.2.0 (2015-12-23)

 * added support for `?` which matches any character
 * added support for character classes `[abc]` which match any of the specified
   characters
 * added support for inverted character classes `[^abc]` which match any but
   the specified characters

* 3.1.1 (2015-08-24)

 * fixed minimum versions in composer.json

* 3.1.0 (2015-08-21)

 * added `TestUtil` class
 * fixed normalizing of slashes on Windows

* 3.0.0 (2015-08-11)

 * `RecursiveDirectoryIterator` now inherits from `\RecursiveDirectoryIterator`
   for performance reasons. Support for `seek()` was removed on PHP versions
   < 5.5.23 or < 5.6.7
 * made `Glob` final

* 2.0.1 (2015-05-21)

 * upgraded to webmozart/path-util 2.0

* 2.0.0 (2015-04-06)

 * restricted `**` to be used within two separators only: `/**/`. This improves
   performance while maintaining equal expressiveness
 * added support for stream wrappers

* 1.0.0 (2015-03-19)

 * added support for sets: `{ab,cd}`
 
* 1.0.0-beta3 (2015-01-30)

 * fixed installation on Windows

* 1.0.0-beta2 (2015-01-22)

 * implemented Ant-like globbing: `*` does not match directory separators
   anymore, but `**` does
 * escaping must now be explicitly enabled by passing the flag `Glob::ESCAPE`
   to any of the `Glob` methods
 * fixed: replaced fatal error by `InvalidArgumentException` when globs are
   not absolute

* 1.0.0-beta (2015-01-12)

 * first release
