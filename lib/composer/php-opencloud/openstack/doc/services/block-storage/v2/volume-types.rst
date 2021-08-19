Volume Types
============

Listing volume types
--------------------
.. sample:: blockstoragev2/volume_types/list.php
.. refdoc:: OpenStack/BlockStorage/v2/Service.html#method_listVolumeTypes

Each iteration will return a :php:class:`VolumeType` instance <OpenStack/BlockStorage/v2/Models/VolumeType.html>.

.. include:: /common/generators.rst


Create volume type
------------------

The only attributes that are required when creating a volume are a name. The simplest example
would therefore be this:

.. sample:: blockstoragev2/volume_types/create.php
.. refdoc:: OpenStack/BlockStorage/v2/Service.html#method_createVolumeType


Retrieve details of a volume type
---------------------------------

When retrieving a volume type, sometimes you only want to operate on it - say to update or delete it. If this is the
case, then there is no need to perform an initial GET request to the API:

.. sample:: blockstoragev2/volume_types/get.php

If, however, you *do* want to retrieve all the details of a remote volume type from the API, you just call:

.. code-block:: php

    $volumeType->retrieve();

which will update the state of the local object. This gives you an element of control over your app's performance.

.. refdoc:: OpenStack/BlockStorage/v2/Service.html#method_getVolumeType


Update a volume type
--------------------

The first step when updating a volume type is modifying the attributes you want updated. By default, only a volume
type's name can be edited.

.. sample:: blockstoragev2/volume_types/update.php
.. refdoc:: OpenStack/BlockStorage/v2/Models/VolumeType.html#method_update


Delete volume type
------------------

To permanently delete a volume type:

.. sample:: blockstoragev2/volume_types/delete.php
.. refdoc:: OpenStack/BlockStorage/v2/Models/VolumeType.html#method_delete