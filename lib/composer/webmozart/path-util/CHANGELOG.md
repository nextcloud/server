Changelog
=========

* 2.3.0 (2015-12-17)

 * added `Url::makeRelative()` for calculating relative paths between URLs
 * fixed `Path::makeRelative()` to trim leading dots when moving outside of
   the base path

* 2.2.3 (2015-10-05)

 * fixed `Path::makeRelative()` to produce `..` when called with the parent
   directory of a path

* 2.2.2 (2015-08-24)

 * `Path::makeAbsolute()` does not fail anymore if an absolute path is passed
   with a different root (partition) than the base path

* 2.2.1 (2015-08-24)

 * fixed minimum versions in composer.json

* 2.2.0 (2015-08-14)

 * added `Path::normalize()`

* 2.1.0 (2015-07-14)

 * `Path::canonicalize()` now turns `~` into the user's home directory on
   Unix and Windows 8 or later.

* 2.0.0 (2015-05-21)

 * added support for streams, e.g. "phar://C:/path/to/file"
 * added `Path::join()`
 * all `Path` methods now throw exceptions if parameters with invalid types are 
   passed
 * added an internal buffer to `Path::canonicalize()` in order to increase the
   performance of the `Path` class

* 1.1.0 (2015-03-19)

 * added `Path::getFilename()`
 * added `Path::getFilenameWithoutExtension()`
 * added `Path::getExtension()`
 * added `Path::hasExtension()`
 * added `Path::changeExtension()`
 * `Path::makeRelative()` now works when the absolute path and the base path
   have equal directory names beneath different base directories
   (e.g. "/webmozart/css/style.css" relative to "/puli/css")
   
* 1.0.2 (2015-01-12)

 * `Path::makeAbsolute()` fails now if the base path is not absolute
 * `Path::makeRelative()` now works when a relative path is passed and the base
   path is empty

* 1.0.1 (2014-12-03)

 * Added PHP 5.6 to Travis.
 * Fixed bug in `Path::makeRelative()` when first argument is shorter than second
 * Made HHVM compatibility mandatory in .travis.yml
 * Added PHP 5.3.3 to travis.yml

* 1.0.0 (2014-11-26)

 * first release
