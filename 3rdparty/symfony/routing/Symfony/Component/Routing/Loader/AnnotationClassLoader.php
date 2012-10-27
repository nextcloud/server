<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;

/**
 * AnnotationClassLoader loads routing information from a PHP class and its methods.
 *
 * You need to define an implementation for the getRouteDefaults() method. Most of the
 * time, this method should define some PHP callable to be called for the route
 * (a controller in MVC speak).
 *
 * The @Route annotation can be set on the class (for global parameters),
 * and on each method.
 *
 * The @Route annotation main value is the route pattern. The annotation also
 * recognizes three parameters: requirements, options, and name. The name parameter
 * is mandatory. Here is an example of how you should be able to use it:
 *
 *     /**
 *      * @Route("/Blog")
 *      * /
 *     class Blog
 *     {
 *         /**
 *          * @Route("/", name="blog_index")
 *          * /
 *         public function index()
 *         {
 *         }
 *
 *         /**
 *          * @Route("/{id}", name="blog_post", requirements = {"id" = "\d+"})
 *          * /
 *         public function show()
 *         {
 *         }
 *     }
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class AnnotationClassLoader implements LoaderInterface
{
    protected $reader;
    protected $routeAnnotationClass  = 'Symfony\\Component\\Routing\\Annotation\\Route';
    protected $defaultRouteIndex;

    /**
     * Constructor.
     *
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Sets the annotation class to read route properties from.
     *
     * @param string $class A fully-qualified class name
     */
    public function setRouteAnnotationClass($class)
    {
        $this->routeAnnotationClass = $class;
    }

    /**
     * Loads from annotations from a class.
     *
     * @param string $class A class name
     * @param string $type  The resource type
     *
     * @return RouteCollection A RouteCollection instance
     *
     * @throws \InvalidArgumentException When route can't be parsed
     */
    public function load($class, $type = null)
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        $globals = array(
            'pattern'      => '',
            'requirements' => array(),
            'options'      => array(),
            'defaults'     => array(),
        );

        $class = new \ReflectionClass($class);
        if ($class->isAbstract()) {
            throw new \InvalidArgumentException(sprintf('Annotations from class "%s" cannot be read as it is abstract.', $class));
        }

        if ($annot = $this->reader->getClassAnnotation($class, $this->routeAnnotationClass)) {
            if (null !== $annot->getPattern()) {
                $globals['pattern'] = $annot->getPattern();
            }

            if (null !== $annot->getRequirements()) {
                $globals['requirements'] = $annot->getRequirements();
            }

            if (null !== $annot->getOptions()) {
                $globals['options'] = $annot->getOptions();
            }

            if (null !== $annot->getDefaults()) {
                $globals['defaults'] = $annot->getDefaults();
            }
        }

        $collection = new RouteCollection();
        $collection->addResource(new FileResource($class->getFileName()));

        foreach ($class->getMethods() as $method) {
            $this->defaultRouteIndex = 0;
            foreach ($this->reader->getMethodAnnotations($method) as $annot) {
                if ($annot instanceof $this->routeAnnotationClass) {
                    $this->addRoute($collection, $annot, $globals, $class, $method);
                }
            }
        }

        return $collection;
    }

    protected function addRoute(RouteCollection $collection, $annot, $globals, \ReflectionClass $class, \ReflectionMethod $method)
    {
        $name = $annot->getName();
        if (null === $name) {
            $name = $this->getDefaultRouteName($class, $method);
        }

        $defaults = array_merge($globals['defaults'], $annot->getDefaults());
        $requirements = array_merge($globals['requirements'], $annot->getRequirements());
        $options = array_merge($globals['options'], $annot->getOptions());

        $route = new Route($globals['pattern'].$annot->getPattern(), $defaults, $requirements, $options);

        $this->configureRoute($route, $class, $method, $annot);

        $collection->add($name, $route);
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean True if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && preg_match('/^(?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+$/', $resource) && (!$type || 'annotation' === $type);
    }

    /**
     * Sets the loader resolver.
     *
     * @param LoaderResolver $resolver A LoaderResolver instance
     */
    public function setResolver(LoaderResolver $resolver)
    {
    }

    /**
     * Gets the loader resolver.
     *
     * @return LoaderResolver A LoaderResolver instance
     */
    public function getResolver()
    {
    }

    /**
     * Gets the default route name for a class method.
     *
     * @param \ReflectionClass  $class
     * @param \ReflectionMethod $method
     *
     * @return string
     */
    protected function getDefaultRouteName(\ReflectionClass $class, \ReflectionMethod $method)
    {
        $name = strtolower(str_replace('\\', '_', $class->name).'_'.$method->name);
        if ($this->defaultRouteIndex > 0) {
            $name .= '_'.$this->defaultRouteIndex;
        }
        $this->defaultRouteIndex++;

        return $name;
    }

    abstract protected function configureRoute(Route $route, \ReflectionClass $class, \ReflectionMethod $method, $annot);
}
