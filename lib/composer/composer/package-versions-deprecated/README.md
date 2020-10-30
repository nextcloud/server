# Package Versions

**`composer/package-versions-deprecated` is a fully-compatible fork of [`ocramius/package-versions`](https://github.com/Ocramius/PackageVersions)** which provides compatibility with Composer 1 and 2 on PHP 7+. It replaces ocramius/package-versions so if you have a dependency requiring it and you want to use Composer v2 but can not upgrade to PHP 7.4 just yet, you can require this package instead.

If you have a direct dependency on ocramius/package-versions, we recommend instead that once you migrated to Composer 2 you also migrate to use the `Composer\Versions` class which offers the functionality present here out of the box.
