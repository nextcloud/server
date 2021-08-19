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

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\RouteCollection;

/**
 * A route loader that calls a method on an object to load the routes.
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
abstract class ObjectLoader extends Loader
{
    /**
     * Returns the object that the method will be called on to load routes.
     *
     * For example, if your application uses a service container,
     * the $id may be a service id.
     *
     * @return object
     */
    abstract protected function getObject(string $id);

    /**
     * Calls the object method that will load the routes.
     *
     * @param string      $resource object_id::method
     * @param string|null $type     The resource type
     *
     * @return RouteCollection
     */
    public function load($resource, $type = null)
    {
        if (!preg_match('/^[^\:]+(?:::?(?:[^\:]+))?$/', $resource)) {
            throw new \InvalidArgumentException(sprintf('Invalid resource "%s" passed to the %s route loader: use the format "object_id::method" or "object_id" if your object class has an "__invoke" method.', $resource, \is_string($type) ? '"'.$type.'"' : 'object'));
        }

        if (1 === substr_count($resource, ':')) {
            $resource = str_replace(':', '::', $resource);
            @trigger_error(sprintf('Referencing object route loaders with a single colon is deprecated since Symfony 4.1. Use %s instead.', $resource), \E_USER_DEPRECATED);
        }

        $parts = explode('::', $resource);
        $method = $parts[1] ?? '__invoke';

        $loaderObject = $this->getObject($parts[0]);

        if (!\is_object($loaderObject)) {
            throw new \TypeError(sprintf('"%s:getObject()" must return an object: "%s" returned.', static::class, \gettype($loaderObject)));
        }

        if (!\is_callable([$loaderObject, $method])) {
            throw new \BadMethodCallException(sprintf('Method "%s" not found on "%s" when importing routing resource "%s".', $method, \get_class($loaderObject), $resource));
        }

        $routeCollection = $loaderObject->$method($this);

        if (!$routeCollection instanceof RouteCollection) {
            $type = \is_object($routeCollection) ? \get_class($routeCollection) : \gettype($routeCollection);

            throw new \LogicException(sprintf('The "%s::%s()" method must return a RouteCollection: "%s" returned.', \get_class($loaderObject), $method, $type));
        }

        // make the object file tracked so that if it changes, the cache rebuilds
        $this->addClassResource(new \ReflectionClass($loaderObject), $routeCollection);

        return $routeCollection;
    }

    private function addClassResource(\ReflectionClass $class, RouteCollection $collection)
    {
        do {
            if (is_file($class->getFileName())) {
                $collection->addResource(new FileResource($class->getFileName()));
            }
        } while ($class = $class->getParentClass());
    }
}
