******************************
Minimal set of PEAR core files
******************************

This repository provides a set of files from ``pear-core``
that are often used in PEAR packages.

It follows the `pear-core`__ repository and gets updated whenever a new
PEAR version is released.

It's meant to be used as dependency for composer packages.

__ https://github.com/pear/pear-core

==============
Included files
==============
- ``OS/Guess.php``
- ``PEAR.php``
- ``PEAR/Error.php``
- ``PEAR/ErrorStack.php``
- ``System.php``


``PEAR/Error.php`` is a dummy file that only includes ``PEAR.php``,
to make autoloaders work without problems.
