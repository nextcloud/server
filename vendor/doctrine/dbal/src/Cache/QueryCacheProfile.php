<?php

namespace Doctrine\DBAL\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\DBAL\Types\Type;
use Doctrine\Deprecations\Deprecation;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use TypeError;

use function class_exists;
use function get_class;
use function hash;
use function serialize;
use function sha1;
use function sprintf;

/**
 * Query Cache Profile handles the data relevant for query caching.
 *
 * It is a value object, setter methods return NEW instances.
 */
class QueryCacheProfile
{
    private ?CacheItemPoolInterface $resultCache = null;

    /** @var int */
    private $lifetime;

    /** @var string|null */
    private $cacheKey;

    /**
     * @param int                               $lifetime
     * @param string|null                       $cacheKey
     * @param CacheItemPoolInterface|Cache|null $resultCache
     */
    public function __construct($lifetime = 0, $cacheKey = null, ?object $resultCache = null)
    {
        $this->lifetime = $lifetime;
        $this->cacheKey = $cacheKey;
        if ($resultCache instanceof CacheItemPoolInterface) {
            $this->resultCache = $resultCache;
        } elseif ($resultCache instanceof Cache) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/4620',
                'Passing an instance of %s to %s as $resultCache is deprecated. Pass an instance of %s instead.',
                Cache::class,
                __METHOD__,
                CacheItemPoolInterface::class,
            );

            $this->resultCache = CacheAdapter::wrap($resultCache);
        } elseif ($resultCache !== null) {
            throw new TypeError(sprintf(
                '$resultCache: Expected either null or an instance of %s or %s, got %s.',
                CacheItemPoolInterface::class,
                Cache::class,
                get_class($resultCache),
            ));
        }
    }

    public function getResultCache(): ?CacheItemPoolInterface
    {
        return $this->resultCache;
    }

    /**
     * @deprecated Use {@see getResultCache()} instead.
     *
     * @return Cache|null
     */
    public function getResultCacheDriver()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4620',
            '%s is deprecated, call getResultCache() instead.',
            __METHOD__,
        );

        if ($this->resultCache === null) {
            return null;
        }

        if (! class_exists(DoctrineProvider::class)) {
            throw new RuntimeException(sprintf(
                'Calling %s() is not supported if the doctrine/cache package is not installed. '
                    . 'Try running "composer require doctrine/cache" or migrate cache access to PSR-6.',
                __METHOD__,
            ));
        }

        return DoctrineProvider::wrap($this->resultCache);
    }

    /** @return int */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * @return string
     *
     * @throws CacheException
     */
    public function getCacheKey()
    {
        if ($this->cacheKey === null) {
            throw CacheException::noCacheKey();
        }

        return $this->cacheKey;
    }

    /**
     * Generates the real cache key from query, params, types and connection parameters.
     *
     * @param string                                                               $sql
     * @param list<mixed>|array<string, mixed>                                     $params
     * @param array<int, Type|int|string|null>|array<string, Type|int|string|null> $types
     * @param array<string, mixed>                                                 $connectionParams
     *
     * @return array{string, string}
     */
    public function generateCacheKeys($sql, $params, $types, array $connectionParams = [])
    {
        if (isset($connectionParams['password'])) {
            unset($connectionParams['password']);
        }

        $realCacheKey = 'query=' . $sql .
            '&params=' . serialize($params) .
            '&types=' . serialize($types) .
            '&connectionParams=' . hash('sha256', serialize($connectionParams));

        // should the key be automatically generated using the inputs or is the cache key set?
        $cacheKey = $this->cacheKey ?? sha1($realCacheKey);

        return [$cacheKey, $realCacheKey];
    }

    public function setResultCache(CacheItemPoolInterface $cache): QueryCacheProfile
    {
        return new QueryCacheProfile($this->lifetime, $this->cacheKey, $cache);
    }

    /**
     * @deprecated Use {@see setResultCache()} instead.
     *
     * @return QueryCacheProfile
     */
    public function setResultCacheDriver(Cache $cache)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/4620',
            '%s is deprecated, call setResultCache() instead.',
            __METHOD__,
        );

        return new QueryCacheProfile($this->lifetime, $this->cacheKey, CacheAdapter::wrap($cache));
    }

    /**
     * @param string|null $cacheKey
     *
     * @return QueryCacheProfile
     */
    public function setCacheKey($cacheKey)
    {
        return new QueryCacheProfile($this->lifetime, $cacheKey, $this->resultCache);
    }

    /**
     * @param int $lifetime
     *
     * @return QueryCacheProfile
     */
    public function setLifetime($lifetime)
    {
        return new QueryCacheProfile($lifetime, $this->cacheKey, $this->resultCache);
    }
}
