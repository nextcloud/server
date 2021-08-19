Images
======

Create image
------------

The only required attribute when creating a new image is ``name``.

.. sample:: images/v2/images/create.php
.. refdoc:: OpenStack/Images/v2/Service.html#method_createImage

List images
-----------

.. sample:: images/v2/images/list.php
.. refdoc:: OpenStack/Images/v2/Service.html#method_listImages

.. include:: /common/generators.rst

Show image details
------------------

.. sample:: images/v2/images/get.php
.. refdoc:: OpenStack/Images/v2/Service.html#method_getImage

Update image
------------

.. sample:: images/v2/images/update.php
.. refdoc:: OpenStack/Images/v2/Models/Image.html#method_update

Delete image
------------

.. sample:: images/v2/images/delete.php
.. refdoc:: OpenStack/Images/v2/Models/Image.html#method_delete

Reactivate image
----------------

.. sample:: images/v2/images/reactivate.php
.. refdoc:: OpenStack/Images/v2/Models/Image.html#method_reactivate

Deactivate image
----------------

If you try to download a deactivated image, a Forbidden error is returned.

.. sample:: images/v2/images/deactivate.php
.. refdoc:: OpenStack/Images/v2/Models/Image.html#method_deactivate

Upload binary data
------------------

Before you can store binary image data, you must meet the following preconditions:

* The image must exist.
* You must set the disk and container formats in the image.
* The image status must be ``queued``.
* Your image storage quota must be sufficient.

The size of the data that you want to store must not exceed the size that the Image service allows.

.. sample:: images/v2/images/upload_binary_data.php
.. refdoc:: OpenStack/Images/v2/Models/Image.html#method_uploadData

Download binary data
--------------------

.. sample:: images/v2/images/download_binary_data.php
.. refdoc:: OpenStack/Images/v2/Models/Image.html#method_downloadData