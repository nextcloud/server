Volumes
=======

List volumes
------------

.. sample:: blockstoragev2/volumes/list.php
.. refdoc:: OpenStack/BlockStorage/v2/Service.html#method_listVolumes

Each iteration will return a php:class:`Volume` instance <OpenStack/BlockStorage/v2/Models/Volume.html>.

.. include:: /common/generators.rst

Detailed information
~~~~~~~~~~~~~~~~~~~~

By default, only the ``id``, ``links`` and ``name`` attributes are returned. To return *all* information
for a flavor, you must enable detailed information, like so:

.. sample:: blockstoragev2/volumes/list_detail.php

Create volume
-------------

The only attributes that are required when creating a volume are a size in GiB. The simplest example
would therefore be this:

.. sample:: blockstoragev2/volumes/create.php

You can further configure your new volume, however, by following the below sections, which instruct you how to add
specific functionality.

.. refdoc:: OpenStack/BlockStorage/v2/Service.html#method_createVolume

Create from image
~~~~~~~~~~~~~~~~~

.. sample:: blockstoragev2/volumes/create_from_image.php

Create from snapshot
~~~~~~~~~~~~~~~~~~~~

.. sample:: blockstoragev2/volumes/create_from_snapshot.php

Create from source volume
~~~~~~~~~~~~~~~~~~~~~~~~~

.. sample:: blockstoragev2/volumes/create_from_source_volume.php


Retrieve volume details
-----------------------

When retrieving a volume, sometimes you only want to operate on it - say to update or delete it. If this is the case,
then there is no need to perform an initial GET request to the API:

.. sample:: blockstoragev2/volumes/get.php

If, however, you *do* want to retrieve all the details of a remote volume from the API, you just call:

.. code-block:: php

    $volume->retrieve();

which will update the state of the local object. This gives you an element of control over your app's performance.

.. refdoc:: OpenStack/BlockStorage/v2/Service.html#method_getVolume


Update volume
-------------

The first step when updating a volume is modifying the attributes you want updated. By default, only a volume's name
and description can be edited.

.. sample:: blockstoragev2/volumes/update.php
.. refdoc:: OpenStack/BlockStorage/v2/Models/Volume.html#method_update

Delete volume
-------------

To permanently delete a volume:

.. sample:: blockstoragev2/volumes/delete.php
.. refdoc:: OpenStack/BlockStorage/v2/Models/Volume.html#method_delete