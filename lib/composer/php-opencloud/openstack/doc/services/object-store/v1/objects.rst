Objects
=======

Show details for an object
--------------------------

.. sample:: objectstore/v1/objects/get.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/Container.html#method_getObject

At this point, the object returned is *empty* because we did not execute a HTTP request to receive the state of the
container from the API. This is in accordance with one of the SDK's general policies of not assuming too much at the
expense of performance.

To synchronize the local object's state with the remote API, you can run:

.. code-block:: php

    $object->retrieve();

    printf("%s/%s is %d bytes long and was last modified on %s",
        $object->containerName, $object->name, $object->contentLength, $object->lastModified);

and all of the local properties will match those of the remote resource. The ``retrieve`` call, although fetching all
of the object's metadata, will not download the object's content. To do this, see the next section.

Download an object
------------------

.. sample:: objectstore/v1/objects/download.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/StorageObject.html#method_download

As you will notice, a Stream_ object is returned by this call. For more information about dealing with streams, please
consult `Guzzle's docs`_.

By default, the whole body of the object is fetched before the function returns, set the ``'requestOptions'`` key of
parameter ``$data`` to ``['stream' => true]`` to get the stream before the end of download.

.. _Stream: https://github.com/guzzle/streams/blob/master/src/Stream.php
.. _Guzzle's docs: https://guzzle.readthedocs.org/en/5.3/streams.html

List objects
------------

.. sample:: objectstore/v1/objects/list.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/Container.html#method_listObjects

When listing objects, you must be aware that not *all* information about a container is returned in a collection.
Very often only the MD5 hash, last modified date, bytes used, content type and object name will be
returned. If you would like to access all of the remote state of a collection item, you can call ``retrieve`` like so:

.. code-block:: php

    foreach ($objects as $object) {
        // To retrieve metadata
        $object->retrieve();
    }

If you have a large collection of $object, this will slow things down because you're issuing a HEAD request per object.

.. include:: /common/generators.rst

Create an object
----------------

When creating an object, you can upload its content according to a string representation:

.. sample:: objectstore/v1/objects/create.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/Container.html#method_createObject

If that is not optimal or convenient, you can use a stream instead. Any instance of ``\Psr\Http\Message\StreamInterface``
is acceptable. For example, to use a normal Guzzle stream:

.. sample:: objectstore/v1/objects/create_from_stream.php

Create a large object (over 5GB)
--------------------------------

For large objects (those over 5GB), you will need to use a concept in Swift called Dynamic Large Objects (DLO). When
uploading, this is what happens under the hood:

1. The large file is separated into smaller segments
2. Each segment is uploaded
3. A manifest file is created which, when requested by clients, will concatenate all the segments as a single file

To upload a DLO, you need to call:

.. sample:: objectstore/v1/objects/create_large_object.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/Container.html#method_createLargeObject

Copy object
-----------

.. sample:: objectstore/v1/objects/copy.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/StorageObject.html#method_copy

Delete object
-------------

.. sample:: objectstore/v1/objects/delete.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/StorageObject.html#method_delete

Get metadata
------------

.. sample:: objectstore/v1/objects/get_metadata.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/StorageObject.html#method_getMetadata

The returned value will be a standard associative array, or hash, containing arbitrary key/value pairs. These will
correspond to the values set either when the object was created, or when a previous ``mergeMetadata`` or
``resetMetadata`` operation was called.

Replace all metadata with new values
------------------------------------

.. sample:: objectstore/v1/objects/reset_metadata.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/StorageObject.html#method_resetMetadata

In order to replace all existing metadata with a set of new values, you can use this operation. Any existing metadata
items which not specified in the new set will be removed. For example, say an account has the following metadata
already set:

::

    Foo: value1
    Bar: value2

and you *reset* the metadata with these values:

::

    Foo: value4
    Baz: value3

the metadata of the account will now be:

::

    Foo: value4
    Baz: value3


Merge new metadata values with existing
---------------------------------------

.. sample:: objectstore/v1/objects/merge_metadata.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/StorageObject.html#method_mergeMetadata

In order to merge a set of new metadata values with the existing metadata set, you can use this operation. Any existing
metadata items which are not specified in the new set will be preserved. For example, say an account has the following
metadata already set:

::

    Foo: value1
    Bar: value2

and you merge them with these values:

::

    Foo: value4
    Baz: value3

the metadata of the account will now be:

::

    Foo: value4
    Bar: value2
    Baz: value3
