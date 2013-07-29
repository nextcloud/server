<?php

namespace Guzzle\Cache;

/**
 * Abstract cache adapter
 */
abstract class AbstractCacheAdapter implements CacheAdapterInterface
{
    protected $cache;

    /**
     * Get the object owned by the adapter
     *
     * @return mixed
     */
    public function getCacheObject()
    {
        return $this->cache;
    }
}
