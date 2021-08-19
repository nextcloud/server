Flavors
=======

List flavors
------------

.. sample:: compute/v2/flavors/list_flavors.php
.. refdoc:: OpenStack/Compute/v2/Service.html#method_listFlavors

Each iteration will return a :php:class:`Flavor` instance <OpenStack/Compute/v2/Models/Flavor.html>.

.. include:: /common/generators.rst

Detailed information
~~~~~~~~~~~~~~~~~~~~

By default, only the ``id``, ``links`` and ``name`` attributes are returned. To return *all* information
for a flavor, you must pass ``true`` as the last parameter, like so:

.. code-block:: php

    $flavors = $service->listFlavors([], function ($flavor) {
        return $flavor;
    }, true);

Retrieve a flavor
-----------------

.. sample:: compute/v2/flavors/get_flavor.php
.. refdoc:: OpenStack/Compute/v2/Service.html#method_getFlavor

When retrieving a flavor, sometimes you only want to operate on it. If this is the case,
then there is no need to perform an initial ``GET`` request to the server:

.. code-block:: php

    // Get an unpopulated object
    $flavor = $service->getFlavor(['id' => '{flavorId}']);

If, however, you *do* want to retrieve all the details of a remote flavor from the API, you just call:

.. code-block:: php

    $flavor->retrieve();

which will update the state of the local object. This gives you an element of control over your app's performance.
