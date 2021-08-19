Containers
==========

Show details for a container
----------------------------

.. sample:: objectstore/v1/containers/get.php
.. refdoc:: OpenStack/ObjectStore/v1/Service.html#method_getContainer

At this point, the object returned is *empty* because we did not execute a HTTP request to receive the state of the
container from the API. This is in accordance with one of the SDK's general policies of not assuming too much at the
expense of performance.

To synchronize the local object's state with the remote API, you can run:

.. code-block:: php

    $container->retrieve();

    printf("%s container has %d objects and %d bytes",
        $container->name, $container->objectCount, $container->bytesUsed);

and all of the local properties will match those of the remote resource.

List containers
---------------

.. sample:: objectstore/v1/containers/list.php
.. refdoc:: OpenStack/ObjectStore/v1/Service.html#method_listContainers

When listing containers, you must be aware that not *all* information about a container is returned in a collection.
Very often only the object count, bytes used and container name will be exposed. If you would like to
access all of the remote state of a collection item, you can call ``retrieve`` like so:

.. code-block:: php

    foreach ($containers as $container) {
        $container->retrieve();
    }

If you have a large collection of containers, this will slow things down because you're issuing a HEAD request per
container.

.. include:: /common/generators.rst

Delete container
----------------

.. sample:: objectstore/v1/containers/delete.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/Container.html#method_delete

The API will only accept DELETE requests on containers when they are empty. If you have a container with any objects
inside, the operation will fail.

Get metadata
------------

.. sample:: objectstore/v1/containers/get_metadata.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/Container.html#method_getMetadata

The returned value will be a standard associative array, or hash, containing arbitrary key/value pairs. These will
correspond to the values set either when the container was created, or when a previous ``mergeMetadata`` or
``resetMetadata`` operation was called.

Replace all metadata with new values
------------------------------------

.. sample:: objectstore/v1/containers/reset_metadata.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/Container.html#method_resetMetadata

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

.. sample:: objectstore/v1/containers/merge_metadata.php
.. refdoc:: OpenStack/ObjectStore/v1/Models/Container.html#method_mergeMetadata

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
