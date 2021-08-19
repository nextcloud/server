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

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouteCompiler;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileLoader loads Yaml routing files.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Tobias Schultze <http://tobion.de>
 */
class YamlFileLoader extends FileLoader
{
    private const AVAILABLE_KEYS = [
        'resource', 'type', 'prefix', 'path', 'host', 'schemes', 'methods', 'defaults', 'requirements', 'options', 'condition', 'controller', 'name_prefix', 'trailing_slash_on_root', 'locale', 'format', 'utf8', 'exclude',
    ];
    private $yamlParser;

    /**
     * Loads a Yaml file.
     *
     * @param string      $file A Yaml file path
     * @param string|null $type The resource type
     *
     * @return RouteCollection A RouteCollection instance
     *
     * @throws \InvalidArgumentException When a route can't be parsed because YAML is invalid
     */
    public function load($file, $type = null)
    {
        $path = $this->locator->locate($file);

        if (!stream_is_local($path)) {
            throw new \InvalidArgumentException(sprintf('This is not a local file "%s".', $path));
        }

        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('File "%s" not found.', $path));
        }

        if (null === $this->yamlParser) {
            $this->yamlParser = new YamlParser();
        }

        try {
            $parsedConfig = $this->yamlParser->parseFile($path, Yaml::PARSE_CONSTANT);
        } catch (ParseException $e) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML: ', $path).$e->getMessage(), 0, $e);
        }

        $collection = new RouteCollection();
        $collection->addResource(new FileResource($path));

        // empty file
        if (null === $parsedConfig) {
            return $collection;
        }

        // not an array
        if (!\is_array($parsedConfig)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" must contain a YAML array.', $path));
        }

        foreach ($parsedConfig as $name => $config) {
            $this->validate($config, $name, $path);

            if (isset($config['resource'])) {
                $this->parseImport($collection, $config, $path, $file);
            } else {
                $this->parseRoute($collection, $name, $config, $path);
            }
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return \is_string($resource) && \in_array(pathinfo($resource, \PATHINFO_EXTENSION), ['yml', 'yaml'], true) && (!$type || 'yaml' === $type);
    }

    /**
     * Parses a route and adds it to the RouteCollection.
     *
     * @param string $name   Route name
     * @param array  $config Route definition
     * @param string $path   Full path of the YAML file being processed
     */
    protected function parseRoute(RouteCollection $collection, $name, array $config, $path)
    {
        $defaults = $config['defaults'] ?? [];
        $requirements = $config['requirements'] ?? [];
        $options = $config['options'] ?? [];
        $host = $config['host'] ?? '';
        $schemes = $config['schemes'] ?? [];
        $methods = $config['methods'] ?? [];
        $condition = $config['condition'] ?? null;

        foreach ($requirements as $placeholder => $requirement) {
            if (\is_int($placeholder)) {
                @trigger_error(sprintf('A placeholder name must be a string (%d given). Did you forget to specify the placeholder key for the requirement "%s" of route "%s" in "%s"?', $placeholder, $requirement, $name, $path), \E_USER_DEPRECATED);
            }
        }

        if (isset($config['controller'])) {
            $defaults['_controller'] = $config['controller'];
        }
        if (isset($config['locale'])) {
            $defaults['_locale'] = $config['locale'];
        }
        if (isset($config['format'])) {
            $defaults['_format'] = $config['format'];
        }
        if (isset($config['utf8'])) {
            $options['utf8'] = $config['utf8'];
        }

        if (\is_array($config['path'])) {
            $route = new Route('', $defaults, $requirements, $options, $host, $schemes, $methods, $condition);

            foreach ($config['path'] as $locale => $path) {
                $localizedRoute = clone $route;
                $localizedRoute->setDefault('_locale', $locale);
                $localizedRoute->setRequirement('_locale', preg_quote($locale, RouteCompiler::REGEX_DELIMITER));
                $localizedRoute->setDefault('_canonical_route', $name);
                $localizedRoute->setPath($path);
                $collection->add($name.'.'.$locale, $localizedRoute);
            }
        } else {
            $route = new Route($config['path'], $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
            $collection->add($name, $route);
        }
    }

    /**
     * Parses an import and adds the routes in the resource to the RouteCollection.
     *
     * @param array  $config Route definition
     * @param string $path   Full path of the YAML file being processed
     * @param string $file   Loaded file name
     */
    protected function parseImport(RouteCollection $collection, array $config, $path, $file)
    {
        $type = $config['type'] ?? null;
        $prefix = $config['prefix'] ?? '';
        $defaults = $config['defaults'] ?? [];
        $requirements = $config['requirements'] ?? [];
        $options = $config['options'] ?? [];
        $host = $config['host'] ?? null;
        $condition = $config['condition'] ?? null;
        $schemes = $config['schemes'] ?? null;
        $methods = $config['methods'] ?? null;
        $trailingSlashOnRoot = $config['trailing_slash_on_root'] ?? true;
        $exclude = $config['exclude'] ?? null;

        if (isset($config['controller'])) {
            $defaults['_controller'] = $config['controller'];
        }
        if (isset($config['locale'])) {
            $defaults['_locale'] = $config['locale'];
        }
        if (isset($config['format'])) {
            $defaults['_format'] = $config['format'];
        }
        if (isset($config['utf8'])) {
            $options['utf8'] = $config['utf8'];
        }

        $this->setCurrentDir(\dirname($path));

        $imported = $this->import($config['resource'], $type, false, $file, $exclude) ?: [];

        if (!\is_array($imported)) {
            $imported = [$imported];
        }

        foreach ($imported as $subCollection) {
            /* @var $subCollection RouteCollection */
            if (!\is_array($prefix)) {
                $subCollection->addPrefix($prefix);
                if (!$trailingSlashOnRoot) {
                    $rootPath = (new Route(trim(trim($prefix), '/').'/'))->getPath();
                    foreach ($subCollection->all() as $route) {
                        if ($route->getPath() === $rootPath) {
                            $route->setPath(rtrim($rootPath, '/'));
                        }
                    }
                }
            } else {
                foreach ($prefix as $locale => $localePrefix) {
                    $prefix[$locale] = trim(trim($localePrefix), '/');
                }
                foreach ($subCollection->all() as $name => $route) {
                    if (null === $locale = $route->getDefault('_locale')) {
                        $subCollection->remove($name);
                        foreach ($prefix as $locale => $localePrefix) {
                            $localizedRoute = clone $route;
                            $localizedRoute->setDefault('_locale', $locale);
                            $localizedRoute->setRequirement('_locale', preg_quote($locale, RouteCompiler::REGEX_DELIMITER));
                            $localizedRoute->setDefault('_canonical_route', $name);
                            $localizedRoute->setPath($localePrefix.(!$trailingSlashOnRoot && '/' === $route->getPath() ? '' : $route->getPath()));
                            $subCollection->add($name.'.'.$locale, $localizedRoute);
                        }
                    } elseif (!isset($prefix[$locale])) {
                        throw new \InvalidArgumentException(sprintf('Route "%s" with locale "%s" is missing a corresponding prefix when imported in "%s".', $name, $locale, $file));
                    } else {
                        $route->setPath($prefix[$locale].(!$trailingSlashOnRoot && '/' === $route->getPath() ? '' : $route->getPath()));
                        $subCollection->add($name, $route);
                    }
                }
            }

            if (null !== $host) {
                $subCollection->setHost($host);
            }
            if (null !== $condition) {
                $subCollection->setCondition($condition);
            }
            if (null !== $schemes) {
                $subCollection->setSchemes($schemes);
            }
            if (null !== $methods) {
                $subCollection->setMethods($methods);
            }
            $subCollection->addDefaults($defaults);
            $subCollection->addRequirements($requirements);
            $subCollection->addOptions($options);

            if (isset($config['name_prefix'])) {
                $subCollection->addNamePrefix($config['name_prefix']);
            }

            $collection->addCollection($subCollection);
        }
    }

    /**
     * Validates the route configuration.
     *
     * @param array  $config A resource config
     * @param string $name   The config key
     * @param string $path   The loaded file path
     *
     * @throws \InvalidArgumentException If one of the provided config keys is not supported,
     *                                   something is missing or the combination is nonsense
     */
    protected function validate($config, $name, $path)
    {
        if (!\is_array($config)) {
            throw new \InvalidArgumentException(sprintf('The definition of "%s" in "%s" must be a YAML array.', $name, $path));
        }
        if ($extraKeys = array_diff(array_keys($config), self::AVAILABLE_KEYS)) {
            throw new \InvalidArgumentException(sprintf('The routing file "%s" contains unsupported keys for "%s": "%s". Expected one of: "%s".', $path, $name, implode('", "', $extraKeys), implode('", "', self::AVAILABLE_KEYS)));
        }
        if (isset($config['resource']) && isset($config['path'])) {
            throw new \InvalidArgumentException(sprintf('The routing file "%s" must not specify both the "resource" key and the "path" key for "%s". Choose between an import and a route definition.', $path, $name));
        }
        if (!isset($config['resource']) && isset($config['type'])) {
            throw new \InvalidArgumentException(sprintf('The "type" key for the route definition "%s" in "%s" is unsupported. It is only available for imports in combination with the "resource" key.', $name, $path));
        }
        if (!isset($config['resource']) && !isset($config['path'])) {
            throw new \InvalidArgumentException(sprintf('You must define a "path" for the route "%s" in file "%s".', $name, $path));
        }
        if (isset($config['controller']) && isset($config['defaults']['_controller'])) {
            throw new \InvalidArgumentException(sprintf('The routing file "%s" must not specify both the "controller" key and the defaults key "_controller" for "%s".', $path, $name));
        }
    }
}
