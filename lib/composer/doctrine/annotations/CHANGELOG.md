## Changelog

### 1.6.1

This release fixes an issue in which annotations such as `@foo-bar`
and `@foo-` were incorrectly recognised as valid, and both erroneously
parsed as `@foo`.

Any annotation with `@name-*` format will now silently be ignored,
allowing vendor-specific annotations to be prefixed with the tool
name.

Total issues resolved: **3**

- [165: Update the composer branch alias](https://github.com/doctrine/annotations/pull/165) thanks to @mikeSimonson
- [209: Change Annotation::value typehint to mixed](https://github.com/doctrine/annotations/pull/209) thanks to @malarzm
- [257: Skip parsing annotations containing dashes, such as `@Foo-bar`, or `@Foo-`](https://github.com/doctrine/annotations/pull/257) thanks to @Ocramius

### 1.6.0

This release brings a new endpoint that make sure that you can't shoot yourself in the foot by calling ```registerLoader``` multiple times and a few tests improvements.

Total issues resolved: **7**

- [145: Memory leak in AnnotationRegistry::registerLoader() when called multiple times](https://github.com/doctrine/annotations/issues/145) thanks to @TriAnMan
- [146: Import error on @experimental Annotation](https://github.com/doctrine/annotations/issues/146) thanks to @aturki
- [147: Ignoring @experimental annotation used by Symfony 3.3 CacheAdapter](https://github.com/doctrine/annotations/pull/147) thanks to @aturki
- [151: Remove duplicate code in `DCOM58Test`](https://github.com/doctrine/annotations/pull/151) thanks to @tuanphpvn
- [161: Prevent loading class&#95;exists multiple times](https://github.com/doctrine/annotations/pull/161) thanks to @jrjohnson
- [162: Add registerUniqueLoader to AnnotationRegistry](https://github.com/doctrine/annotations/pull/162) thanks to @jrjohnson
- [163: Use assertDirectoryExists and assertDirectoryNotExists](https://github.com/doctrine/annotations/pull/163) thanks to @carusogabriel

Thanks to everyone involved in this release.

### 1.5.0

This release increments the minimum supported PHP version to 7.1.0.

Also, HHVM official support has been dropped.

Some noticeable performance improvements to annotation autoloading
have been applied, making failed annotation autoloading less heavy
on the filesystem access.

- [133: Add @throws annotation in AnnotationReader#__construct()](https://github.com/doctrine/annotations/issues/133) thanks to @SenseException
- [134: Require PHP 7.1, drop HHVM support](https://github.com/doctrine/annotations/issues/134) thanks to @lcobucci
- [135: Prevent the same loader from being registered twice](https://github.com/doctrine/annotations/issues/135)  thanks to @jrjohnson
- [137: #135 optimise multiple class load attempts in AnnotationRegistry](https://github.com/doctrine/annotations/issues/137)  thanks to @Ocramius


### 1.4.0

This release fix an issue were some annotations could be not loaded if the namespace in the use statement started with a backslash.
It also update the tests and drop the support for php 5.X

- [115: Missing annotations with the latest composer version](https://github.com/doctrine/annotations/issues/115) thanks to @pascalporedda
- [120: Missing annotations with the latest composer version](https://github.com/doctrine/annotations/pull/120) thanks to @gnat42
- [121: Adding a more detailed explanation of the test](https://github.com/doctrine/annotations/pull/121) thanks to @mikeSimonson
- [101: Test annotation parameters containing space](https://github.com/doctrine/annotations/pull/101) thanks to @mikeSimonson
- [111: Cleanup: move to correct phpunit assertions](https://github.com/doctrine/annotations/pull/111) thanks to @Ocramius
- [112: Removes support for PHP 5.x](https://github.com/doctrine/annotations/pull/112) thanks to @railto
- [113: bumped phpunit version to 5.7](https://github.com/doctrine/annotations/pull/113) thanks to @gabbydgab
- [114: Enhancement: Use SVG Travis build badge](https://github.com/doctrine/annotations/pull/114) thanks to @localheinz
- [118: Integrating PHPStan](https://github.com/doctrine/annotations/pull/118) thanks to @ondrejmirtes

### 1.3.1 - 2016-12-30

This release fixes an issue with ignored annotations that were already
autoloaded, causing the `SimpleAnnotationReader` to pick them up
anyway. [#110](https://github.com/doctrine/annotations/pull/110)

Additionally, an issue was fixed in the `CachedReader`, which was
not correctly checking the freshness of cached annotations when
traits were defined on a class. [#105](https://github.com/doctrine/annotations/pull/105)

Total issues resolved: **2**

- [105: Return single max timestamp](https://github.com/doctrine/annotations/pull/105)
- [110: setIgnoreNotImportedAnnotations(true) didn&rsquo;t work for existing classes](https://github.com/doctrine/annotations/pull/110)

### 1.3.0

This release introduces a PHP version bump. `doctrine/annotations` now requires PHP
5.6 or later to be installed.

A series of additional improvements have been introduced:

 * support for PHP 7 "grouped use statements"
 * support for ignoring entire namespace names
   via `Doctrine\Common\Annotations\AnnotationReader::addGlobalIgnoredNamespace()` and
   `Doctrine\Common\Annotations\DocParser::setIgnoredAnnotationNamespaces()`. This will
   allow you to ignore annotations from namespaces that you cannot autoload
 * testing all parent classes and interfaces when checking if the annotation cache
   in the `CachedReader` is fresh
 * simplifying the cache keys used by the `CachedReader`: keys are no longer artificially
   namespaced, since `Doctrine\Common\Cache` already supports that
 * corrected parsing of multibyte strings when `mbstring.func_overload` is enabled
 * corrected parsing of annotations when `"\t"` is put before the first annotation
   in a docblock
 * allow skipping non-imported annotations when a custom `DocParser` is passed to
   the `AnnotationReader` constructor

Total issues resolved: **15**

- [45: DocParser can now ignore whole namespaces](https://github.com/doctrine/annotations/pull/45)
- [57: Switch to the docker-based infrastructure on Travis](https://github.com/doctrine/annotations/pull/57)
- [59: opcache.load&#95;comments has been removed from PHP 7](https://github.com/doctrine/annotations/pull/59)
- [62: &#91;CachedReader&#92; Test traits and parent class to see if cache is fresh](https://github.com/doctrine/annotations/pull/62)
- [65: Remove cache salt making key unnecessarily long](https://github.com/doctrine/annotations/pull/65)
- [66: Fix of incorrect parsing multibyte strings](https://github.com/doctrine/annotations/pull/66)
- [68: Annotations that are indented by tab are not processed.](https://github.com/doctrine/annotations/issues/68)
- [69: Support for Group Use Statements](https://github.com/doctrine/annotations/pull/69)
- [70: Allow tab character before first annotation in DocBlock](https://github.com/doctrine/annotations/pull/70)
- [74: Ignore not registered annotations fix](https://github.com/doctrine/annotations/pull/74)
- [92: Added tests for AnnotationRegistry class.](https://github.com/doctrine/annotations/pull/92)
- [96: Fix/#62 check trait and parent class ttl in annotations](https://github.com/doctrine/annotations/pull/96)
- [97: Feature - #45 - allow ignoring entire namespaces](https://github.com/doctrine/annotations/pull/97)
- [98: Enhancement/#65 remove cache salt from cached reader](https://github.com/doctrine/annotations/pull/98)
- [99: Fix - #70 - allow tab character before first annotation in docblock](https://github.com/doctrine/annotations/pull/99)

### 1.2.4

Total issues resolved: **1**

- [51: FileCacheReader::saveCacheFile::unlink fix](https://github.com/doctrine/annotations/pull/51)

### 1.2.3

Total issues resolved: [**2**](https://github.com/doctrine/annotations/milestones/v1.2.3)

- [49: #46 - applying correct `chmod()` to generated cache file](https://github.com/doctrine/annotations/pull/49)
- [50: Hotfix: match escaped quotes (revert #44)](https://github.com/doctrine/annotations/pull/50)

### 1.2.2

Total issues resolved: **4**

- [43: Exclude files from distribution with .gitattributes](https://github.com/doctrine/annotations/pull/43)
- [44: Update DocLexer.php](https://github.com/doctrine/annotations/pull/44)
- [46: A plain &quot;file&#95;put&#95;contents&quot; can cause havoc](https://github.com/doctrine/annotations/pull/46)
- [48: Deprecating the `FileCacheReader` in 1.2.2: will be removed in 2.0.0](https://github.com/doctrine/annotations/pull/48)

### 1.2.1

Total issues resolved: **4**

- [38: fixes doctrine/common#326](https://github.com/doctrine/annotations/pull/38)
- [39: Remove superfluous NS](https://github.com/doctrine/annotations/pull/39)
- [41: Warn if load_comments is not enabled.](https://github.com/doctrine/annotations/pull/41)
- [42: Clean up unused uses](https://github.com/doctrine/annotations/pull/42)

### 1.2.0

 * HHVM support
 * Allowing dangling comma in annotations
 * Excluded annotations are no longer autoloaded
 * Importing namespaces also in traits
 * Added support for `::class` 5.5-style constant, works also in 5.3 and 5.4

### 1.1.0

 * Add Exception when ZendOptimizer+ or Opcache is configured to drop comments
