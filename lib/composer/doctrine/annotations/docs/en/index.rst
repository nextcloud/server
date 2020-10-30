Introduction
============

Doctrine Annotations allows to implement custom annotation
functionality for PHP classes.

.. code-block:: php

    class Foo
    {
        /**
         * @MyAnnotation(myProperty="value")
         */
        private $bar;
    }

Annotations aren't implemented in PHP itself which is why this component
offers a way to use the PHP doc-blocks as a place for the well known
annotation syntax using the ``@`` char.

Annotations in Doctrine are used for the ORM configuration to build the
class mapping, but it can be used in other projects for other purposes
too.

Installation
============

You can install the Annotation component with composer:

.. code-block::

    $ composer require doctrine/annotations

Create an annotation class
==========================

An annotation class is a representation of the later used annotation
configuration in classes. The annotation class of the previous example
looks like this:

.. code-block:: php

    /**
     * @Annotation
     */
    final class MyAnnotation
    {
        public $myProperty;
    }

The annotation class is declared as an annotation by ``@Annotation``.

:ref:`Read more about custom annotations. <custom>`

Reading annotations
===================

The access to the annotations happens by reflection of the class
containing them. There are multiple reader-classes implementing the
``Doctrine\Common\Annotations\Reader`` interface, that can access the
annotations of a class. A common one is
``Doctrine\Common\Annotations\AnnotationReader``:

.. code-block:: php

    use Doctrine\Common\Annotations\AnnotationReader;
    use Doctrine\Common\Annotations\AnnotationRegistry;

    // Deprecated and will be removed in 2.0 but currently needed
    AnnotationRegistry::registerLoader('class_exists');

    $reflectionClass = new ReflectionClass(Foo::class);
    $property = $reflectionClass->getProperty('bar');

    $reader = new AnnotationReader();
    $myAnnotation = $reader->getPropertyAnnotation($property, MyAnnotation::class);

    echo $myAnnotation->myProperty; // result: "value"

Note that ``AnnotationRegistry::registerLoader('class_exists')`` only works
if you already have an autoloader configured (i.e. composer autoloader).
Otherwise, :ref:`please take a look to the other annotation autoload mechanisms <annotations>`.

A reader has multiple methods to access the annotations of a class.

:ref:`Read more about handling annotations. <annotations>`

IDE Support
-----------

Some IDEs already provide support for annotations:

- Eclipse via the `Symfony2 Plugin <http://symfony.dubture.com/>`_
- PHPStorm via the `PHP Annotations Plugin <http://plugins.jetbrains.com/plugin/7320>`_ or the `Symfony2 Plugin <http://plugins.jetbrains.com/plugin/7219>`_

.. _Read more about handling annotations.: annotations
.. _Read more about custom annotations.: custom
