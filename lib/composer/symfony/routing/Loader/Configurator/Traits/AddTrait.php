<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader\Configurator\Traits;

use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RouteConfigurator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouteCompiler;

trait AddTrait
{
    /**
     * @var RouteCollection
     */
    private $collection;

    private $name = '';

    private $prefixes;

    /**
     * Adds a route.
     *
     * @param string|array $path the path, or the localized paths of the route
     */
    final public function add(string $name, $path): RouteConfigurator
    {
        $paths = [];
        $parentConfigurator = $this instanceof CollectionConfigurator ? $this : ($this instanceof RouteConfigurator ? $this->parentConfigurator : null);

        if (\is_array($path)) {
            if (null === $this->prefixes) {
                $paths = $path;
            } elseif ($missing = array_diff_key($this->prefixes, $path)) {
                throw new \LogicException(sprintf('Route "%s" is missing routes for locale(s) "%s".', $name, implode('", "', array_keys($missing))));
            } else {
                foreach ($path as $locale => $localePath) {
                    if (!isset($this->prefixes[$locale])) {
                        throw new \LogicException(sprintf('Route "%s" with locale "%s" is missing a corresponding prefix in its parent collection.', $name, $locale));
                    }

                    $paths[$locale] = $this->prefixes[$locale].$localePath;
                }
            }
        } elseif (null !== $this->prefixes) {
            foreach ($this->prefixes as $locale => $prefix) {
                $paths[$locale] = $prefix.$path;
            }
        } else {
            $this->collection->add($this->name.$name, $route = $this->createRoute($path));

            return new RouteConfigurator($this->collection, $route, $this->name, $parentConfigurator, $this->prefixes);
        }

        $routes = new RouteCollection();

        foreach ($paths as $locale => $path) {
            $routes->add($name.'.'.$locale, $route = $this->createRoute($path));
            $this->collection->add($this->name.$name.'.'.$locale, $route);
            $route->setDefault('_locale', $locale);
            $route->setRequirement('_locale', preg_quote($locale, RouteCompiler::REGEX_DELIMITER));
            $route->setDefault('_canonical_route', $this->name.$name);
        }

        return new RouteConfigurator($this->collection, $routes, $this->name, $parentConfigurator, $this->prefixes);
    }

    /**
     * Adds a route.
     *
     * @param string|array $path the path, or the localized paths of the route
     */
    final public function __invoke(string $name, $path): RouteConfigurator
    {
        return $this->add($name, $path);
    }

    private function createRoute(string $path): Route
    {
        return new Route($path);
    }
}
