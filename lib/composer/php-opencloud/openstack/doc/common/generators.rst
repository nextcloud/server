By default, `PHP generators`_ are used to represent collections of resources in the SDK. The benefit of using
generators is that it generally improves performance, since objects are not saved in memory as the iteration cycle goes
on; instead, each resource is directly output to the user-defined ``foreach`` loop. For all intents and purposes, you
interact with generators like any other `Traversable object`_, but to retain collections in memory, you will need to
implement your own logic.

.. _PHP generators: http://php.net/manual/en/language.generators.overview.php
.. _Traversable object: http://php.net/manual/en/language.generators.overview.php