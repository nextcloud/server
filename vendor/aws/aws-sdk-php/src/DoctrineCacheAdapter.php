<?php
namespace Aws;

use Doctrine\Common\Cache\Cache;

class DoctrineCacheAdapter implements CacheInterface, Cache
{
    /** @var Cache */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function get($key)
    {
        return $this->cache->fetch($key);
    }

    /**
     * @return mixed
     */
    public function fetch($key)
    {
        return $this->get($key);
    }

    public function set($key, $value, $ttl = 0)
    {
        return $this->cache->save($key, $value, $ttl);
    }

    /**
     * @return bool
     */
    public function save($key, $value, $ttl = 0)
    {
        return $this->set($key, $value, $ttl);
    }

    public function remove($key)
    {
        return $this->cache->delete($key);
    }

    /**
     * @return bool
     */
    public function delete($key)
    {
        return $this->remove($key);
    }

    /**
     * @return bool
     */
    public function contains($key)
    {
        return $this->cache->contains($key);
    }

    /**
     * @return mixed[]|null
     */
    public function getStats()
    {
        return $this->cache->getStats();
    }
}
