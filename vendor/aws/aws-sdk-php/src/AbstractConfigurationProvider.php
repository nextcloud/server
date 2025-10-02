<?php
namespace Aws;

use GuzzleHttp\Promise;

/**
 * A configuration provider is a function that returns a promise that is
 * fulfilled with a configuration object. This class provides base functionality
 * usable by specific configuration provider implementations
 */
abstract class AbstractConfigurationProvider
{
    const ENV_PROFILE = 'AWS_PROFILE';
    const ENV_CONFIG_FILE = 'AWS_CONFIG_FILE';

    public static $cacheKey;

    protected static $interfaceClass;
    protected static $exceptionClass;

    /**
     * Wraps a config provider and saves provided configuration in an
     * instance of Aws\CacheInterface. Forwards calls when no config found
     * in cache and updates cache with the results.
     *
     * @param callable $provider Configuration provider function to wrap
     * @param CacheInterface $cache Cache to store configuration
     * @param string|null $cacheKey (optional) Cache key to use
     *
     * @return callable
     */
    public static function cache(
        callable $provider,
        CacheInterface $cache,
        $cacheKey = null
    ) {
        $cacheKey = $cacheKey ?: static::$cacheKey;

        return function () use ($provider, $cache, $cacheKey) {
            $found = $cache->get($cacheKey);
            if ($found instanceof static::$interfaceClass) {
                return Promise\Create::promiseFor($found);
            }

            return $provider()
                ->then(function ($config) use (
                    $cache,
                    $cacheKey
                ) {
                    $cache->set($cacheKey, $config);
                    return $config;
                });
        };
    }

    /**
     * Creates an aggregate configuration provider that invokes the provided
     * variadic providers one after the other until a provider returns
     * configuration.
     *
     * @return callable
     */
    public static function chain()
    {
        $links = func_get_args();
        if (empty($links)) {
            throw new \InvalidArgumentException('No providers in chain');
        }

        return function () use ($links) {
            /** @var callable $parent */
            $parent = array_shift($links);
            $promise = $parent();
            while ($next = array_shift($links)) {
                $promise = $promise->otherwise($next);
            }
            return $promise;
        };
    }

    /**
     * Gets the environment's HOME directory if available.
     *
     * @return null|string
     */
    protected static function getHomeDir()
    {
        // On Linux/Unix-like systems, use the HOME environment variable
        if ($homeDir = getenv('HOME')) {
            return $homeDir;
        }

        // Get the HOMEDRIVE and HOMEPATH values for Windows hosts
        $homeDrive = getenv('HOMEDRIVE');
        $homePath = getenv('HOMEPATH');

        return ($homeDrive && $homePath) ? $homeDrive . $homePath : null;
    }

    /**
     * Gets default config file location from environment, falling back to aws
     * default location
     *
     * @return string
     */
    protected static function getDefaultConfigFilename()
    {
        if ($filename = getenv(self::ENV_CONFIG_FILE)) {
            return $filename;
        }
        return self::getHomeDir() . '/.aws/config';
    }

    /**
     * Wraps a config provider and caches previously provided configuration.
     *
     * @param callable $provider Config provider function to wrap.
     *
     * @return callable
     */
    public static function memoize(callable $provider)
    {
        return function () use ($provider) {
            static $result;
            static $isConstant;

            // Constant config will be returned constantly.
            if ($isConstant) {
                return $result;
            }

            // Create the initial promise that will be used as the cached value
            if (null === $result) {
                $result = $provider();
            }

            // Return config and set flag that provider is already set
            return $result
                ->then(function ($config) use (&$isConstant) {
                    $isConstant = true;
                    return $config;
                });
        };
    }

    /**
     * Reject promise with standardized exception.
     *
     * @param $msg
     * @return Promise\RejectedPromise
     */
    protected static function reject($msg)
    {
        $exceptionClass = static::$exceptionClass;
        return new Promise\RejectedPromise(new $exceptionClass($msg));
    }
}
