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

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Loader\FileLoader;

/**
 * YamlFileLoader loads Yaml routing files.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @api
 */
class YamlFileLoader extends FileLoader
{
    private static $availableKeys = array(
        'type', 'resource', 'prefix', 'pattern', 'options', 'defaults', 'requirements'
    );

    /**
     * Loads a Yaml file.
     *
     * @param string $file A Yaml file path
     * @param string $type The resource type
     *
     * @return RouteCollection A RouteCollection instance
     *
     * @throws \InvalidArgumentException When route can't be parsed
     *
     * @api
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        $config = Yaml::parse($path);

        $collection = new RouteCollection();
        $collection->addResource(new FileResource($path));

        // empty file
        if (null === $config) {
            $config = array();
        }

        // not an array
        if (!is_array($config)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" must contain a YAML array.', $file));
        }

        foreach ($config as $name => $config) {
            $config = $this->normalizeRouteConfig($config);

            if (isset($config['resource'])) {
                $type = isset($config['type']) ? $config['type'] : null;
                $prefix = isset($config['prefix']) ? $config['prefix'] : null;
                $this->setCurrentDir(dirname($path));
                $collection->addCollection($this->import($config['resource'], $type, false, $file), $prefix);
            } else {
                $this->parseRoute($collection, $name, $config, $path);
            }
        }

        return $collection;
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string $type     The resource type
     *
     * @return Boolean True if this class supports the given resource, false otherwise
     *
     * @api
     */
    public function supports($resource, $type = null)
    {
        return is_string($resource) && 'yml' === pathinfo($resource, PATHINFO_EXTENSION) && (!$type || 'yaml' === $type);
    }

    /**
     * Parses a route and adds it to the RouteCollection.
     *
     * @param RouteCollection $collection A RouteCollection instance
     * @param string          $name       Route name
     * @param array           $config     Route definition
     * @param string          $file       A Yaml file path
     *
     * @throws \InvalidArgumentException When config pattern is not defined for the given route
     */
    protected function parseRoute(RouteCollection $collection, $name, $config, $file)
    {
        $defaults = isset($config['defaults']) ? $config['defaults'] : array();
        $requirements = isset($config['requirements']) ? $config['requirements'] : array();
        $options = isset($config['options']) ? $config['options'] : array();

        if (!isset($config['pattern'])) {
            throw new \InvalidArgumentException(sprintf('You must define a "pattern" for the "%s" route.', $name));
        }

        $route = new Route($config['pattern'], $defaults, $requirements, $options);

        $collection->add($name, $route);
    }

    /**
     * Normalize route configuration.
     *
     * @param array $config A resource config
     *
     * @return array
     *
     * @throws InvalidArgumentException if one of the provided config keys is not supported
     */
    private function normalizeRouteConfig(array $config)
    {
        foreach ($config as $key => $value) {
            if (!in_array($key, self::$availableKeys)) {
                throw new \InvalidArgumentException(sprintf(
                    'Yaml routing loader does not support given key: "%s". Expected one of the (%s).',
                    $key, implode(', ', self::$availableKeys)
                ));
            }
        }

        return $config;
    }
}
