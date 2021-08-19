Archive_Tar
==========

![.github/workflows/build.yml](https://github.com/pear/Archive_Tar/workflows/.github/workflows/build.yml/badge.svg)

This package provides handling of tar files in PHP.
It supports creating, listing, extracting and adding to tar files.
Gzip support is available if PHP has the zlib extension built-in or
loaded. Bz2 compression is also supported with the bz2 extension loaded.
Also Lzma2 compressed archives are supported with xz extension.

This package is hosted at http://pear.php.net/package/Archive_Tar

Please report all new issues via the PEAR bug tracker.

Pull requests are welcome!


Testing, building
-----------------

To test, run either
$ phpunit tests/
  or
$ pear run-tests -r

To build, simply
$ pear package

To install from scratch
$ pear install package.xml

To upgrade
$ pear upgrade -f package.xml
