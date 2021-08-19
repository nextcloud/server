<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing;

use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * Helps add and import routes into a RouteCollection.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
class RouteCollectionBuilder
{
    /**
     * @var Route[]|RouteCollectionBuilder[]
     */
    private $routes = [];

    private $loader;
    private $defaults = [];
    private $prefix;
    private $host;
    private $condition;
    private $requirements = [];
    private $options = [];
    private $schemes;
    private $methods;
    private $resources = [];

    public function __construct(LoaderInterface $loader = null)
    {
        $this->loader = $loader;
    }

    /**
     * Import an external routing resource and returns the RouteCollectionBuilder.
     *
     *     $routes->import('blog.yml', '/blog');
     *
     * @param mixed       $resource
     * @param string|null $prefix
     * @param string      $type
     *
     * @return self
     *
     * @throws LoaderLoadException
     */
    public function import($resource, $prefix = '/', $type = null)
    {
        /** @var RouteCollection[] $collections */
        $collections = $this->load($resource, $type);

        // create a builder from the RouteCollection
        $builder = $this->createBuilder();

        foreach ($collections as $collection) {
            if (null === $collection) {
                continue;
            }

            foreach ($collection->all() as $name => $route) {
                $builder->addRoute($route, $name);
            }

            foreach ($collection->getResources() as $resource) {
                $builder->addResource($resource);
            }
        }

        // mount into this builder
        $this->mount($prefix, $builder);

        return $builder;
    }

    /**
     * Adds a route and returns it for future modification.
     *
     * @param string      $path       The route path
     * @param string      $controller The route's controller
     * @param string|null $name       The name to give this route
     *
     * @return Route
     */
    public function add($path, $controller, $name = null)
    {
        $route = new Route($path);
        $route->setDefault('_controller', $controller);
        $this->addRoute($route, $name);

        return $route;
    }

    /**
     * Returns a RouteCollectionBuilder that can be configured and then added with mount().
     *
     * @return self
     */
    public function createBuilder()
    {
        return new self($this->loader);
    }

    /**
     * Add a RouteCollectionBuilder.
     *
     * @param string $prefix
     */
    public function mount($prefix, self $builder)
    {
        $builder->prefix = trim(trim($prefix), '/');
        $this->routes[] = $builder;
    }

    /**
     * Adds a Route object to the builder.
     *
     * @param string|null $name
     *
     * @return $this
     */
    public function addRoute(Route $route, $name = null)
    {
        if (null === $name) {
            // used as a flag to know which routes will need a name later
            $name = '_unnamed_route_'.spl_object_hash($route);
        }

        $this->routes[$name] = $route;

        return $this;
    }

    /**
     * Sets the host on all embedded routes (unless already set).
     *
     * @param string $pattern
     *
     * @return $this
     */
    public function setHost($pattern)
    {
        $this->host = $pattern;

        return $this;
    }

    /**
     * Sets a condition on all embedded routes (unless already set).
     *
     * @param string $condition
     *
     * @return $this
     */
    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Sets a default value that will be added to all embedded routes (unless that
     * default value is already set).
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setDefault($key, $value)
    {
        $this->defaults[$key] = $value;

        return $this;
    }

    /**
     * Sets a requirement that will be added to all embedded routes (unless that
     * requirement is already set).
     *
     * @param string $key
     * @param mixed  $regex
     *
     * @return $this
     */
    public function setRequirement($key, $regex)
    {
        $this->requirements[$key] = $regex;

        return $this;
    }

    /**
     * Sets an option that will be added to all embedded routes (unless that
     * option is already set).
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Sets the schemes on all embedded routes (unless already set).
     *
     * @param array|string $schemes
     *
     * @return $this
     */
    public function setSchemes($schemes)
    {
        $this->schemes = $schemes;

        return $this;
    }

    /**
     * Sets the methods on all embedded routes (unless already set).
     *
     * @param array|string $methods
     *
     * @return $this
     */
    public function setMethods($methods)
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * Adds a resource for this collection.
     *
     * @return $this
     */
    private function addResource(ResourceInterface $resource): self
    {
        $this->resources[] = $resource;

        return $this;
    }

    /**
     * Creates the final RouteCollection and returns it.
     *
     * @return RouteCollection
     */
    public function build()
    {
        $routeCollection = new RouteCollection();

        foreach ($this->routes as $name => $route) {
            if ($route instanceof Route) {
                $route->setDefaults(array_merge($this->defaults, $route->getDefaults()));
                $route->setOptions(array_merge($this->options, $route->getOptions()));

                foreach ($this->requirements as $key => $val) {
                    if (!$route->hasRequirement($key)) {
                        $route->setRequirement($key, $val);
                    }
                }

                if (null !== $this->prefix) {
                    $route->setPath('/'.$this->prefix.$route->getPath());
                }

                if (!$route->getHost()) {
                    $route->setHost($this->host);
                }

                if (!$route->getCondition()) {
                    $route->setCondition($this->condition);
                }

                if (!$route->getSchemes()) {
                    $route->setSchemes($this->schemes);
                }

                if (!$route->getMethods()) {
                    $route->setMethods($this->methods);
                }

                // auto-generate the route name if it's been marked
                if ('_unnamed_route_' === substr($name, 0, 15)) {
                    $name = $this->generateRouteName($route);
                }

                $routeCollection->add($name, $route);
            } else {
                /* @var self $route */
                $subCollection = $route->build();
                if (null !== $this->prefix) {
                    $subCollection->addPrefix($this->prefix);
                }

                $routeCollection->addCollection($subCollection);
            }
        }

        foreach ($this->resources as $resource) {
            $routeCollection->addResource($resource);
        }

        return $routeCollection;
    }

    /**
     * Generates a route name based on details of this route.
     */
    private function generateRouteName(Route $route): string
    {
        $methods = implode('_', $route->getMethods()).'_';

        $routeName = $methods.$route->getPath();
        $routeName = str_replace(['/', ':', '|', '-'], '_', $routeName);
        $routeName = preg_replace('/[^a-z0-9A-Z_.]+/', '', $routeName);

        // Collapse consecutive underscores down into a single underscore.
        $routeName = preg_replace('/_+/', '_', $routeName);

        return $routeName;
    }

    /**
     * Finds a loader able to load an imported resource and loads it.
     *
     * @param mixed       $resource A resource
     * @param string|null $type     The resource type or null if unknown
     *
     * @return RouteCollection[]
     *
     * @throws LoaderLoadException If no loader is found
     */
    private function load($resource, string $type = null): array
    {
        if (null === $this->loader) {
            throw new \BadMethodCallException('Cannot import other routing resources: you must pass a LoaderInterface when constructing RouteCollectionBuilder.');
        }

        if ($this->loader->supports($resource, $type)) {
            $collections = $this->loader->load($resource, $type);

            return \is_array($collections) ? $collections : [$collections];
        }

        if (null === $resolver = $this->loader->getResolver()) {
            throw new LoaderLoadException($resource, null, 0, null, $type);
        }

        if (false === $loader = $resolver->resolve($resource, $type)) {
            throw new LoaderLoadException($resource, null, 0, null, $type);
        }

        $collections = $loader->load($resource, $type);

        return \is_array($collections) ? $collections : [$collections];
    }
}
