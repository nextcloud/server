Handling Annotations
====================

There are several different approaches to handling annotations in PHP.
Doctrine Annotations maps docblock annotations to PHP classes. Because
not all docblock annotations are used for metadata purposes a filter is
applied to ignore or skip classes that are not Doctrine annotations.

Take a look at the following code snippet:

.. code-block:: php

    namespace MyProject\Entities;

    use Doctrine\ORM\Mapping AS ORM;
    use Symfony\Component\Validation\Constraints AS Assert;

    /**
     * @author Benjamin Eberlei
     * @ORM\Entity
     * @MyProject\Annotations\Foobarable
     */
    class User
    {
        /**
         * @ORM\Id @ORM\Column @ORM\GeneratedValue
         * @dummy
         * @var int
         */
        private $id;

        /**
         * @ORM\Column(type="string")
         * @Assert\NotEmpty
         * @Assert\Email
         * @var string
         */
        private $email;
    }

In this snippet you can see a variety of different docblock annotations:

- Documentation annotations such as ``@var`` and ``@author``. These
  annotations are on a blacklist and never considered for throwing an
  exception due to wrongly used annotations.
- Annotations imported through use statements. The statement ``use
  Doctrine\ORM\Mapping AS ORM`` makes all classes under that namespace
  available as ``@ORM\ClassName``. Same goes for the import of
  ``@Assert``.
- The ``@dummy`` annotation. It is not a documentation annotation and
  not blacklisted. For Doctrine Annotations it is not entirely clear how
  to handle this annotation. Depending on the configuration an exception
  (unknown annotation) will be thrown when parsing this annotation.
- The fully qualified annotation ``@MyProject\Annotations\Foobarable``.
  This is transformed directly into the given class name.

How are these annotations loaded? From looking at the code you could
guess that the ORM Mapping, Assert Validation and the fully qualified
annotation can just be loaded using
the defined PHP autoloaders. This is not the case however: For error
handling reasons every check for class existence inside the
``AnnotationReader`` sets the second parameter $autoload
of ``class_exists($name, $autoload)`` to false. To work flawlessly the
``AnnotationReader`` requires silent autoloaders which many autoloaders are
not. Silent autoloading is NOT part of the `PSR-0 specification
<https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md>`_
for autoloading.

This is why Doctrine Annotations uses its own autoloading mechanism
through a global registry. If you are wondering about the annotation
registry being global, there is no other way to solve the architectural
problems of autoloading annotation classes in a straightforward fashion.
Additionally if you think about PHP autoloading then you recognize it is
a global as well.

To anticipate the configuration section, making the above PHP class work
with Doctrine Annotations requires this setup:

.. code-block:: php

    use Doctrine\Common\Annotations\AnnotationReader;
    use Doctrine\Common\Annotations\AnnotationRegistry;

    AnnotationRegistry::registerFile("/path/to/doctrine/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");
    AnnotationRegistry::registerAutoloadNamespace("Symfony\Component\Validator\Constraint", "/path/to/symfony/src");
    AnnotationRegistry::registerAutoloadNamespace("MyProject\Annotations", "/path/to/myproject/src");

    $reader = new AnnotationReader();
    AnnotationReader::addGlobalIgnoredName('dummy');

The second block with the annotation registry calls registers all the
three different annotation namespaces that are used.
Doctrine Annotations saves all its annotations in a single file, that is
why ``AnnotationRegistry#registerFile`` is used in contrast to
``AnnotationRegistry#registerAutoloadNamespace`` which creates a PSR-0
compatible loading mechanism for class to file names.

In the third block, we create the actual ``AnnotationReader`` instance.
Note that we also add ``dummy`` to the global list of ignored
annotations for which we do not throw exceptions. Setting this is
necessary in our example case, otherwise ``@dummy`` would trigger an
exception to be thrown during the parsing of the docblock of
``MyProject\Entities\User#id``.

Setup and Configuration
-----------------------

To use the annotations library is simple, you just need to create a new
``AnnotationReader`` instance:

.. code-block:: php

    $reader = new \Doctrine\Common\Annotations\AnnotationReader();

This creates a simple annotation reader with no caching other than in
memory (in php arrays). Since parsing docblocks can be expensive you
should cache this process by using a caching reader.

You can use a file caching reader, but please note it is deprecated to
do so:

.. code-block:: php

    use Doctrine\Common\Annotations\FileCacheReader;
    use Doctrine\Common\Annotations\AnnotationReader;

    $reader = new FileCacheReader(
        new AnnotationReader(),
        "/path/to/cache",
        $debug = true
    );

If you set the ``debug`` flag to ``true`` the cache reader will check
for changes in the original files, which is very important during
development. If you don't set it to ``true`` you have to delete the
directory to clear the cache. This gives faster performance, however
should only be used in production, because of its inconvenience during
development.

You can also use one of the ``Doctrine\Common\Cache\Cache`` cache
implementations to cache the annotations:

.. code-block:: php

    use Doctrine\Common\Annotations\AnnotationReader;
    use Doctrine\Common\Annotations\CachedReader;
    use Doctrine\Common\Cache\ApcCache;

    $reader = new CachedReader(
        new AnnotationReader(),
        new ApcCache(),
        $debug = true
    );

The ``debug`` flag is used here as well to invalidate the cache files
when the PHP class with annotations changed and should be used during
development.

.. warning ::

    The ``AnnotationReader`` works and caches under the
    assumption that all annotations of a doc-block are processed at
    once. That means that annotation classes that do not exist and
    aren't loaded and cannot be autoloaded (using the
    AnnotationRegistry) would never be visible and not accessible if a
    cache is used unless the cache is cleared and the annotations
    requested again, this time with all annotations defined.

By default the annotation reader returns a list of annotations with
numeric indexes. If you want your annotations to be indexed by their
class name you can wrap the reader in an ``IndexedReader``:

.. code-block:: php

    use Doctrine\Common\Annotations\AnnotationReader;
    use Doctrine\Common\Annotations\IndexedReader;

    $reader = new IndexedReader(new AnnotationReader());

.. warning::

    You should never wrap the indexed reader inside a cached reader,
    only the other way around. This way you can re-use the cache with
    indexed or numeric keys, otherwise your code may experience failures
    due to caching in a numerical or indexed format.

Registering Annotations
~~~~~~~~~~~~~~~~~~~~~~~

As explained in the introduction, Doctrine Annotations uses its own
autoloading mechanism to determine if a given annotation has a
corresponding PHP class that can be autoloaded. For annotation
autoloading you have to configure the
``Doctrine\Common\Annotations\AnnotationRegistry``. There are three
different mechanisms to configure annotation autoloading:

- Calling ``AnnotationRegistry#registerFile($file)`` to register a file
  that contains one or more annotation classes.
- Calling ``AnnotationRegistry#registerNamespace($namespace, $dirs =
  null)`` to register that the given namespace contains annotations and
  that their base directory is located at the given $dirs or in the
  include path if ``NULL`` is passed. The given directories should *NOT*
  be the directory where classes of the namespace are in, but the base
  directory of the root namespace. The AnnotationRegistry uses a
  namespace to directory separator approach to resolve the correct path.
- Calling ``AnnotationRegistry#registerLoader($callable)`` to register
  an autoloader callback. The callback accepts the class as first and
  only parameter and has to return ``true`` if the corresponding file
  was found and included.

.. note::

    Loaders have to fail silently, if a class is not found even if it
    matches for example the namespace prefix of that loader. Never is a
    loader to throw a warning or exception if the loading failed
    otherwise parsing doc block annotations will become a huge pain.

A sample loader callback could look like:

.. code-block:: php

    use Doctrine\Common\Annotations\AnnotationRegistry;
    use Symfony\Component\ClassLoader\UniversalClassLoader;

    AnnotationRegistry::registerLoader(function($class) {
        $file = str_replace("\\", DIRECTORY_SEPARATOR, $class) . ".php";

        if (file_exists("/my/base/path/" . $file)) {
            // file_exists() makes sure that the loader fails silently
            require "/my/base/path/" . $file;
        }
    });

    $loader = new UniversalClassLoader();
    AnnotationRegistry::registerLoader(array($loader, "loadClass"));


Ignoring missing exceptions
~~~~~~~~~~~~~~~~~~~~~~~~~~~

By default an exception is thrown from the ``AnnotationReader`` if an
annotation was found that:

- is not part of the blacklist of ignored "documentation annotations";
- was not imported through a use statement;
- is not a fully qualified class that exists.

You can disable this behavior for specific names if your docblocks do
not follow strict requirements:

.. code-block:: php

    $reader = new \Doctrine\Common\Annotations\AnnotationReader();
    AnnotationReader::addGlobalIgnoredName('foo');

PHP Imports
~~~~~~~~~~~

By default the annotation reader parses the use-statement of a php file
to gain access to the import rules and register them for the annotation
processing. Only if you are using PHP Imports can you validate the
correct usage of annotations and throw exceptions if you misspelled an
annotation. This mechanism is enabled by default.

To ease the upgrade path, we still allow you to disable this mechanism.
Note however that we will remove this in future versions:

.. code-block:: php

    $reader = new \Doctrine\Common\Annotations\AnnotationReader();
    $reader->setEnabledPhpImports(false);
