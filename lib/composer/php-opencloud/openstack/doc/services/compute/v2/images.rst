Images
======

List images
-----------

.. sample:: compute/v2/images/list_images.php

Each iteration will return an :apiref:Image instance <OpenStack/Compute/v2/Models/Image.html>_.

.. include:: /common/generators.rst
.. refdoc:: OpenStack/Compute/v2/Service.html#method_listImages

Detailed information
~~~~~~~~~~~~~~~~~~~~

By default, only the id, links and name attributes are returned. To return *all* information
for an image, you must enable detailed information, like so:

.. code-block:: php

    $images = $service->listImages(true);

Retrieve an image
-----------------

When retrieving an image, sometimes you only want to operate on it - say to update or delete it. If this is the case,
then there is no need to perform an initial GET request to the server:

.. sample:: compute/v2/images/get_image.php
.. refdoc:: OpenStack/Compute/v2/Service.html#method_getImage

If, however, you *do* want to retrieve all the details of a remote image from the API, you just call:

.. code-block:: php

    $image->retrieve();

which will update the state of the local object. This gives you an element of control over your app's performance.

Delete an image
---------------

.. sample:: compute/v2/images/delete_image.php
.. refdoc:: OpenStack/Compute/v2/Models/Image.html#method_delete

Retrieve metadata
-----------------

This operation will retrieve the existing metadata for an image:

.. code-block:: php

    $metadata = $image->getMetadata();

.. refdoc:: OpenStack/Compute/v2/Models/Image.html#method_getMetadata

Reset metadata
--------------

.. sample:: compute/v2/images/reset_image_metadata.php

This operation will _replace_ all existing metadata with whatever is provided in the request. Any existing metadata
not specified in the request will be deleted.

.. refdoc:: OpenStack/Compute/v2/Models/Image.html#method_resetMetadata

Merge metadata
--------------

This operation will _merge_ specified metadata with what already exists. Existing values will be overriden, new values
will be added. Any existing keys that are not specified in the request will remain unaffected.

.. code-block:: php

    $image->mergeMetadata([
        'foo' => 'bar',
    ]);

.. refdoc:: OpenStack/Compute/v2/Models/Image.html#method_mergeMetadata

Retrieve image metadata item
----------------------------

This operation allows you to retrieve the value for a specific metadata item:

.. code-block:: php

    $itemValue = $image->getMetadataItem('key');

.. refdoc:: OpenStack/Compute/v2/Models/Image.html#method_getMetadataItem

Delete image metadata item
--------------------------

This operation allows you to remove a specific metadata item:

.. sample:: compute/v2/images/delete_image_metadata_item.php
.. refdoc:: OpenStack/Compute/v2/Models/Image.html#method_deleteMetadataItem