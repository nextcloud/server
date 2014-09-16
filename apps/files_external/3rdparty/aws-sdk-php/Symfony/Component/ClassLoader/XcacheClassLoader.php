<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\ClassLoader;

/**
 * XcacheClassLoader implements a wrapping autoloader cached in Xcache for PHP 5.3.
 *
 * It expects an object implementing a findFile method to find the file. This
 * allows using it as a wrapper around the other loaders of the component (the
 * ClassLoader and the UniversalClassLoader for instance) but also around any
 * other autoloader following this convention (the Composer one for instance)
 *
 *     $loader = new ClassLoader();
 *
 *     // register classes with namespaces
 *     $loader->add('Symfony\Component', __DIR__.'/component');
 *     $loader->add('Symfony',           __DIR__.'/framework');
 *
 *     $cachedLoader = new XcacheClassLoader('my_prefix', $loader);
 *
 *     // activate the cached autoloader
 *     $cachedLoader->register();
 *
 *     // eventually deactivate the non-cached loader if it was registered previously
 *     // to be sure to use the cached one.
 *     $loader->unregister();
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Kris Wallsmith <kris@symfony.com>
 * @author Kim Hemsø Rasmussen <kimhemsoe@gmail.com>
 *
 * @api
 */
class XcacheClassLoader
{
    private $prefix;
    private $classFinder;

    /**
     * Constructor.
     *
     * @param string $prefix      A prefix to create a namespace in Xcache
     * @param object $classFinder An object that implements findFile() method.
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     *
     * @api
     */
    public function __construct($prefix, $classFinder)
    {
        if (!extension_loaded('Xcache')) {
            throw new \RuntimeException('Unable to use XcacheClassLoader as Xcache is not enabled.');
        }

        if (!method_exists($classFinder, 'findFile')) {
            throw new \InvalidArgumentException('The class finder must implement a "findFile" method.');
        }

        $this->prefix = $prefix;
        $this->classFinder = $classFinder;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool    $prepend Whether to prepend the autoloader or not
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $class The name of the class
     *
     * @return bool|null    True, if loaded
     */
    public function loadClass($class)
    {
        if ($file = $this->findFile($class)) {
            require $file;

            return true;
        }
    }

    /**
     * Finds a file by class name while caching lookups to Xcache.
     *
     * @param string $class A class name to resolve to file
     *
     * @return string|null
     */
    public function findFile($class)
    {
        if (xcache_isset($this->prefix.$class)) {
            $file = xcache_get($this->prefix.$class);
        } else {
            $file = $this->classFinder->findFile($class);
            xcache_set($this->prefix.$class, $file);
        }

        return $file;
    }
}
