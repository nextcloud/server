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

use Symfony\Component\Config\Resource\ResourceInterface;

/**
 * A RouteCollection represents a set of Route instances.
 *
 * When adding a route, it overrides existing routes with the
 * same name defined in the instance or its children and parents.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class RouteCollection implements \IteratorAggregate
{
    private $routes;
    private $resources;
    private $prefix;
    private $parent;

    /**
     * Constructor.
     *
     * @api
     */
    public function __construct()
    {
        $this->routes = array();
        $this->resources = array();
        $this->prefix = '';
    }

    public function __clone()
    {
        foreach ($this->routes as $name => $route) {
            $this->routes[$name] = clone $route;
            if ($route instanceof RouteCollection) {
                $this->routes[$name]->setParent($this);
            }
        }
    }

    /**
     * Gets the parent RouteCollection.
     *
     * @return RouteCollection The parent RouteCollection
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets the parent RouteCollection.
     *
     * @param RouteCollection $parent The parent RouteCollection
     */
    public function setParent(RouteCollection $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Gets the current RouteCollection as an Iterator.
     *
     * @return \ArrayIterator An \ArrayIterator interface
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->routes);
    }

    /**
     * Adds a route.
     *
     * @param string $name  The route name
     * @param Route  $route A Route instance
     *
     * @throws \InvalidArgumentException When route name contains non valid characters
     *
     * @api
     */
    public function add($name, Route $route)
    {
        if (!preg_match('/^[a-z0-9A-Z_.]+$/', $name)) {
            throw new \InvalidArgumentException(sprintf('The provided route name "%s" contains non valid characters. A route name must only contain digits (0-9), letters (a-z and A-Z), underscores (_) and dots (.).', $name));
        }

        $parent = $this;
        while ($parent->getParent()) {
            $parent = $parent->getParent();
        }

        if ($parent) {
            $parent->remove($name);
        }

        $this->routes[$name] = $route;
    }

    /**
     * Returns the array of routes.
     *
     * @return array An array of routes
     */
    public function all()
    {
        $routes = array();
        foreach ($this->routes as $name => $route) {
            if ($route instanceof RouteCollection) {
                $routes = array_merge($routes, $route->all());
            } else {
                $routes[$name] = $route;
            }
        }

        return $routes;
    }

    /**
     * Gets a route by name.
     *
     * @param string $name The route name
     *
     * @return Route  $route A Route instance
     */
    public function get($name)
    {
        // get the latest defined route
        foreach (array_reverse($this->routes) as $routes) {
            if (!$routes instanceof RouteCollection) {
                continue;
            }

            if (null !== $route = $routes->get($name)) {
                return $route;
            }
        }

        if (isset($this->routes[$name])) {
            return $this->routes[$name];
        }
    }

    /**
     * Removes a route by name.
     *
     * @param string $name The route name
     */
    public function remove($name)
    {
        if (isset($this->routes[$name])) {
            unset($this->routes[$name]);
        }

        foreach ($this->routes as $routes) {
            if ($routes instanceof RouteCollection) {
                $routes->remove($name);
            }
        }
    }

    /**
     * Adds a route collection to the current set of routes (at the end of the current set).
     *
     * @param RouteCollection $collection A RouteCollection instance
     * @param string          $prefix     An optional prefix to add before each pattern of the route collection
     *
     * @api
     */
    public function addCollection(RouteCollection $collection, $prefix = '')
    {
        $collection->setParent($this);
        $collection->addPrefix($prefix);

        // remove all routes with the same name in all existing collections
        foreach (array_keys($collection->all()) as $name) {
            $this->remove($name);
        }

        $this->routes[] = $collection;
    }

    /**
     * Adds a prefix to all routes in the current set.
     *
     * @param string $prefix An optional prefix to add before each pattern of the route collection
     *
     * @api
     */
    public function addPrefix($prefix)
    {
        // a prefix must not end with a slash
        $prefix = rtrim($prefix, '/');

        if (!$prefix) {
            return;
        }

        // a prefix must start with a slash
        if ('/' !== $prefix[0]) {
            $prefix = '/'.$prefix;
        }

        $this->prefix = $prefix.$this->prefix;

        foreach ($this->routes as $name => $route) {
            if ($route instanceof RouteCollection) {
                $route->addPrefix($prefix);
            } else {
                $route->setPattern($prefix.$route->getPattern());
            }
        }
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Returns an array of resources loaded to build this collection.
     *
     * @return ResourceInterface[] An array of resources
     */
    public function getResources()
    {
        $resources = $this->resources;
        foreach ($this as $routes) {
            if ($routes instanceof RouteCollection) {
                $resources = array_merge($resources, $routes->getResources());
            }
        }

        return array_unique($resources);
    }

    /**
     * Adds a resource for this collection.
     *
     * @param ResourceInterface $resource A resource instance
     */
    public function addResource(ResourceInterface $resource)
    {
        $this->resources[] = $resource;
    }
}
