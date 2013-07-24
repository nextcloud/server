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
 * ClassLoader implements an PSR-0 class loader
 *
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
 *
 *     $loader = new ClassLoader();
 *
 *     // register classes with namespaces
 *     $loader->addPrefix('Symfony\Component', __DIR__.'/component');
 *     $loader->addPrefix('Symfony',           __DIR__.'/framework');
 *
 *     // activate the autoloader
 *     $loader->register();
 *
 *     // to enable searching the include path (e.g. for PEAR packages)
 *     $loader->setUseIncludePath(true);
 *
 * In this example, if you try to use a class in the Symfony\Component
 * namespace or one of its children (Symfony\Component\Console for instance),
 * the autoloader will first look for the class under the component/
 * directory, and it will then fallback to the framework/ directory if not
 * found before giving up.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class ClassLoader
{
    private $prefixes = array();
    private $fallbackDirs = array();
    private $useIncludePath = false;

    /**
     * Returns prefixes.
     *
     * @return array
     */
    public function getPrefixes()
    {
        return $this->prefixes;
    }

    /**
     * Returns fallback directories.
     *
     * @return array
     */
    public function getFallbackDirs()
    {
        return $this->fallbackDirs;
    }

    /**
     * Adds prefixes.
     *
     * @param array $prefixes Prefixes to add
     */
    public function addPrefixes(array $prefixes)
    {
        foreach ($prefixes as $prefix => $path) {
            $this->addPrefix($prefix, $path);
        }
    }

    /**
     * Registers a set of classes
     *
     * @param string       $prefix The classes prefix
     * @param array|string $paths  The location(s) of the classes
     */
    public function addPrefix($prefix, $paths)
    {
        if (!$prefix) {
            foreach ((array) $paths as $path) {
                $this->fallbackDirs[] = $path;
            }

            return;
        }
        if (isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = array_merge(
                $this->prefixes[$prefix],
                (array) $paths
            );
        } else {
            $this->prefixes[$prefix] = (array) $paths;
        }
    }

    /**
     * Turns on searching the include for class files.
     *
     * @param Boolean $useIncludePath
     */
    public function setUseIncludePath($useIncludePath)
    {
        $this->useIncludePath = $useIncludePath;
    }

    /**
     * Can be used to check if the autoloader uses the include path to check
     * for classes.
     *
     * @return Boolean
     */
    public function getUseIncludePath()
    {
        return $this->useIncludePath;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param Boolean $prepend Whether to prepend the autoloader or not
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
     * @return Boolean|null True, if loaded
     */
    public function loadClass($class)
    {
        if ($file = $this->findFile($class)) {
            require $file;

            return true;
        }
    }

    /**
     * Finds the path to the file where the class is defined.
     *
     * @param string $class The name of the class
     *
     * @return string|null The path, if found
     */
    public function findFile($class)
    {
        if (false !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $classPath = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, 0, $pos)).DIRECTORY_SEPARATOR;
            $className = substr($class, $pos + 1);
        } else {
            // PEAR-like class name
            $classPath = null;
            $className = $class;
        }

        $classPath .= str_replace('_', DIRECTORY_SEPARATOR, $className).'.php';

        foreach ($this->prefixes as $prefix => $dirs) {
            if (0 === strpos($class, $prefix)) {
                foreach ($dirs as $dir) {
                    if (file_exists($dir.DIRECTORY_SEPARATOR.$classPath)) {
                        return $dir.DIRECTORY_SEPARATOR.$classPath;
                    }
                }
            }
        }

        foreach ($this->fallbackDirs as $dir) {
            if (file_exists($dir.DIRECTORY_SEPARATOR.$classPath)) {
                return $dir.DIRECTORY_SEPARATOR.$classPath;
            }
        }

        if ($this->useIncludePath && $file = stream_resolve_include_path($classPath)) {
            return $file;
        }
    }
}
