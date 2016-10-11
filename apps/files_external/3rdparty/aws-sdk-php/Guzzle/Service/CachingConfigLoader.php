<?php

namespace Guzzle\Service;

use Guzzle\Cache\CacheAdapterInterface;

/**
 * Decorator that adds caching to a service description loader
 */
class CachingConfigLoader implements ConfigLoaderInterface
{
    /** @var ConfigLoaderInterface */
    protected $loader;

    /** @var CacheAdapterInterface */
    protected $cache;

    /**
     * @param ConfigLoaderInterface $loader Loader used to load the config when there is a cache miss
     * @param CacheAdapterInterface $cache  Object used to cache the loaded result
     */
    public function __construct(ConfigLoaderInterface $loader, CacheAdapterInterface $cache)
    {
        $this->loader = $loader;
        $this->cache = $cache;
    }

    public function load($config, array $options = array())
    {
        if (!is_string($config)) {
            $key = false;
        } else {
            $key = 'loader_' . crc32($config);
            if ($result = $this->cache->fetch($key)) {
                return $result;
            }
        }

        $result = $this->loader->load($config, $options);
        if ($key) {
            $this->cache->save($key, $result);
        }

        return $result;
    }
}
