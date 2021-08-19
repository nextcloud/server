Welcome to the OpenStack SDK for PHP!
=====================================

Requirements
------------

* PHP 7
* cURL extension

Installation
------------

You must install this library through Composer:

.. code-block:: bash

    composer require php-opencloud/openstack

If you do not have Composer installed, please read the `Composer installation instructions`_.

Once you have installed the SDK as a dependency of your project, you will need to load Composer’s autoloader
(which registers all the required namespaces). To do this, place the following line of PHP code at the top of your
application’s PHP files:

.. code-block:: php

    require 'vendor/autoload.php';

This assumes your application's PHP files are located in the same folder as ``vendor/``. If your files are located
elsewhere, please supply the path to vendor/autoload.php in the require statement above.

Supported services
------------------

.. toctree::
    :glob:
    :maxdepth: 1

    services/**/index

Help and support
----------------

If you have specific problems or bugs with this SDK, please file an issue on our official `Github repo`_. We also
have a `mailing list`_, so feel free to join to keep up to date with all the latest changes and announcements to the
library.

For general feedback and support requests, send an email to sdk-support@rackspace.com.

You can also find assistance via IRC on #rackspace at freenode.net.

Contributing
------------

If you'd like to contribute to the project, or require help running the unit/integration tests, please view the
`contributing guidelines`_.

.. _Composer installation instructions: `https://getcomposer.org/doc/00-intro.md`
.. _Github repo: `https://github.com/php-opencloud/openstack`
.. _mailing list: `https://groups.google.com/forum/#!forum/php-opencloud`
.. _contributing guidelines: `https://github.com/php-opencloud/openstack/blob/master/CONTRIBUTING.md`
