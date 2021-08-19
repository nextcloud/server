<?php

namespace Doctrine\Common\Cache\Psr6;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\MultiDeleteCache;
use Doctrine\Common\Cache\MultiGetCache;
use Doctrine\Common\Cache\MultiPutCache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\DoctrineProvider as SymfonyDoctrineProvider;

use function array_key_exists;
use function assert;
use function count;
use function current;
use function get_class;
use function gettype;
use function is_object;
use function is_string;
use function microtime;
use function sprintf;
use function strpbrk;

use const PHP_VERSION_ID;

final class CacheAdapter implements CacheItemPoolInterface
{
    private const RESERVED_CHARACTERS = '{}()/\@:';

    /** @var Cache */
    private $cache;

    /** @var array<CacheItem|TypedCacheItem> */
    private $deferredItems = [];

    public static function wrap(Cache $cache): CacheItemPoolInterface
    {
        if ($cache instanceof DoctrineProvider && ! $cache->getNamespace()) {
            return $cache->getPool();
        }

        if ($cache instanceof SymfonyDoctrineProvider && ! $cache->getNamespace()) {
            $getPool = function () {
                // phpcs:ignore Squiz.Scope.StaticThisUsage.Found
                return $this->pool;
            };

            return $getPool->bindTo($cache, SymfonyDoctrineProvider::class)();
        }

        return new self($cache);
    }

    private function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /** @internal */
    public function getCache(): Cache
    {
        return $this->cache;
    }

    /**
     * {@inheritDoc}
     */
    public function getItem($key): CacheItemInterface
    {
        assert(self::validKey($key));

        if (isset($this->deferredItems[$key])) {
            $this->commit();
        }

        $value = $this->cache->fetch($key);

        if (PHP_VERSION_ID >= 80000) {
            if ($value !== false) {
                return new TypedCacheItem($key, $value, true);
            }

            return new TypedCacheItem($key, null, false);
        }

        if ($value !== false) {
            return new CacheItem($key, $value, true);
        }

        return new CacheItem($key, null, false);
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = []): array
    {
        if ($this->deferredItems) {
            $this->commit();
        }

        assert(self::validKeys($keys));

        $values = $this->doFetchMultiple($keys);
        $items  = [];

        if (PHP_VERSION_ID >= 80000) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $values)) {
                    $items[$key] = new TypedCacheItem($key, $values[$key], true);
                } else {
                    $items[$key] = new TypedCacheItem($key, null, false);
                }
            }

            return $items;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $values)) {
                $items[$key] = new CacheItem($key, $values[$key], true);
            } else {
                $items[$key] = new CacheItem($key, null, false);
            }
        }

        return $items;
    }

    /**
     * {@inheritDoc}
     */
    public function hasItem($key): bool
    {
        assert(self::validKey($key));

        if (isset($this->deferredItems[$key])) {
            $this->commit();
        }

        return $this->cache->contains($key);
    }

    public function clear(): bool
    {
        $this->deferredItems = [];

        if (! $this->cache instanceof ClearableCache) {
            return false;
        }

        return $this->cache->deleteAll();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItem($key): bool
    {
        assert(self::validKey($key));
        unset($this->deferredItems[$key]);

        return $this->cache->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            assert(self::validKey($key));
            unset($this->deferredItems[$key]);
        }

        return $this->doDeleteMultiple($keys);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->saveDeferred($item) && $this->commit();
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        if (! $item instanceof CacheItem && ! $item instanceof TypedCacheItem) {
            return false;
        }

        $this->deferredItems[$item->getKey()] = $item;

        return true;
    }

    public function commit(): bool
    {
        if (! $this->deferredItems) {
            return true;
        }

        $now         = microtime(true);
        $itemsCount  = 0;
        $byLifetime  = [];
        $expiredKeys = [];

        foreach ($this->deferredItems as $key => $item) {
            $lifetime = ($item->getExpiry() ?? $now) - $now;

            if ($lifetime < 0) {
                $expiredKeys[] = $key;

                continue;
            }

            ++$itemsCount;
            $byLifetime[(int) $lifetime][$key] = $item->get();
        }

        $this->deferredItems = [];

        switch (count($expiredKeys)) {
            case 0:
                break;
            case 1:
                $this->cache->delete(current($expiredKeys));
                break;
            default:
                $this->doDeleteMultiple($expiredKeys);
                break;
        }

        if ($itemsCount === 1) {
            return $this->cache->save($key, $item->get(), (int) $lifetime);
        }

        $success = true;
        foreach ($byLifetime as $lifetime => $values) {
            $success = $this->doSaveMultiple($values, $lifetime) && $success;
        }

        return $success;
    }

    public function __destruct()
    {
        $this->commit();
    }

    /**
     * @param mixed $key
     */
    private static function validKey($key): bool
    {
        if (! is_string($key)) {
            throw new InvalidArgument(sprintf('Cache key must be string, "%s" given.', is_object($key) ? get_class($key) : gettype($key)));
        }

        if ($key === '') {
            throw new InvalidArgument('Cache key length must be greater than zero.');
        }

        if (strpbrk($key, self::RESERVED_CHARACTERS) !== false) {
            throw new InvalidArgument(sprintf('Cache key "%s" contains reserved characters "%s".', $key, self::RESERVED_CHARACTERS));
        }

        return true;
    }

    /**
     * @param mixed[] $keys
     */
    private static function validKeys(array $keys): bool
    {
        foreach ($keys as $key) {
            self::validKey($key);
        }

        return true;
    }

    /**
     * @param mixed[] $keys
     */
    private function doDeleteMultiple(array $keys): bool
    {
        if ($this->cache instanceof MultiDeleteCache) {
            return $this->cache->deleteMultiple($keys);
        }

        $success = true;
        foreach ($keys as $key) {
            $success = $this->cache->delete($key) && $success;
        }

        return $success;
    }

    /**
     * @param mixed[] $keys
     *
     * @return mixed[]
     */
    private function doFetchMultiple(array $keys): array
    {
        if ($this->cache instanceof MultiGetCache) {
            return $this->cache->fetchMultiple($keys);
        }

        $values = [];
        foreach ($keys as $key) {
            $value = $this->cache->fetch($key);
            if (! $value) {
                continue;
            }

            $values[$key] = $value;
        }

        return $values;
    }

    /**
     * @param mixed[] $keysAndValues
     */
    private function doSaveMultiple(array $keysAndValues, int $lifetime = 0): bool
    {
        if ($this->cache instanceof MultiPutCache) {
            return $this->cache->saveMultiple($keysAndValues, $lifetime);
        }

        $success = true;
        foreach ($keysAndValues as $key => $value) {
            $success = $this->cache->save($key, $value, $lifetime) && $success;
        }

        return $success;
    }
}
