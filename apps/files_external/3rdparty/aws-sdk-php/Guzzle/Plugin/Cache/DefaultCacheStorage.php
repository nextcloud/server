<?php

namespace Guzzle\Plugin\Cache;

use Guzzle\Cache\CacheAdapterFactory;
use Guzzle\Cache\CacheAdapterInterface;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\Message\MessageInterface;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

/**
 * Default cache storage implementation
 */
class DefaultCacheStorage implements CacheStorageInterface
{
    /** @var string */
    protected $keyPrefix;

    /** @var CacheAdapterInterface Cache used to store cache data */
    protected $cache;

    /** @var int Default cache TTL */
    protected $defaultTtl;

    /**
     * @param mixed  $cache      Cache used to store cache data
     * @param string $keyPrefix  Provide an optional key prefix to prefix on all cache keys
     * @param int    $defaultTtl Default cache TTL
     */
    public function __construct($cache, $keyPrefix = '', $defaultTtl = 3600)
    {
        $this->cache = CacheAdapterFactory::fromCache($cache);
        $this->defaultTtl = $defaultTtl;
        $this->keyPrefix = $keyPrefix;
    }

    public function cache(RequestInterface $request, Response $response)
    {
        $currentTime = time();
        $ttl = $request->getParams()->get('cache.override_ttl') ?: $response->getMaxAge() ?: $this->defaultTtl;

        if ($cacheControl = $response->getHeader('Cache-Control')) {
            $stale = $cacheControl->getDirective('stale-if-error');
            $ttl += $stale == true ? $ttl : $stale;
        }

        // Determine which manifest key should be used
        $key = $this->getCacheKey($request);
        $persistedRequest = $this->persistHeaders($request);
        $entries = array();

        if ($manifest = $this->cache->fetch($key)) {
            // Determine which cache entries should still be in the cache
            $vary = $response->getVary();
            foreach (unserialize($manifest) as $entry) {
                // Check if the entry is expired
                if ($entry[4] < $currentTime) {
                    continue;
                }
                $entry[1]['vary'] = isset($entry[1]['vary']) ? $entry[1]['vary'] : '';
                if ($vary != $entry[1]['vary'] || !$this->requestsMatch($vary, $entry[0], $persistedRequest)) {
                    $entries[] = $entry;
                }
            }
        }

        // Persist the response body if needed
        $bodyDigest = null;
        if ($response->getBody() && $response->getBody()->getContentLength() > 0) {
            $bodyDigest = $this->getBodyKey($request->getUrl(), $response->getBody());
            $this->cache->save($bodyDigest, (string) $response->getBody(), $ttl);
        }

        array_unshift($entries, array(
            $persistedRequest,
            $this->persistHeaders($response),
            $response->getStatusCode(),
            $bodyDigest,
            $currentTime + $ttl
        ));

        $this->cache->save($key, serialize($entries));
    }

    public function delete(RequestInterface $request)
    {
        $key = $this->getCacheKey($request);
        if ($entries = $this->cache->fetch($key)) {
            // Delete each cached body
            foreach (unserialize($entries) as $entry) {
                if ($entry[3]) {
                    $this->cache->delete($entry[3]);
                }
            }
            $this->cache->delete($key);
        }
    }

    public function purge($url)
    {
        foreach (array('GET', 'HEAD', 'POST', 'PUT', 'DELETE') as $method) {
            $this->delete(new Request($method, $url));
        }
    }

    public function fetch(RequestInterface $request)
    {
        $key = $this->getCacheKey($request);
        if (!($entries = $this->cache->fetch($key))) {
            return null;
        }

        $match = null;
        $headers = $this->persistHeaders($request);
        $entries = unserialize($entries);
        foreach ($entries as $index => $entry) {
            if ($this->requestsMatch(isset($entry[1]['vary']) ? $entry[1]['vary'] : '', $headers, $entry[0])) {
                $match = $entry;
                break;
            }
        }

        if (!$match) {
            return null;
        }

        // Ensure that the response is not expired
        $response = null;
        if ($match[4] < time()) {
            $response = -1;
        } else {
            $response = new Response($match[2], $match[1]);
            if ($match[3]) {
                if ($body = $this->cache->fetch($match[3])) {
                    $response->setBody($body);
                } else {
                    // The response is not valid because the body was somehow deleted
                    $response = -1;
                }
            }
        }

        if ($response === -1) {
            // Remove the entry from the metadata and update the cache
            unset($entries[$index]);
            if ($entries) {
                $this->cache->save($key, serialize($entries));
            } else {
                $this->cache->delete($key);
            }
            return null;
        }

        return $response;
    }

    /**
     * Hash a request URL into a string that returns cache metadata
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function getCacheKey(RequestInterface $request)
    {
        // Allow cache.key_filter to trim down the URL cache key by removing generate query string values (e.g. auth)
        if ($filter = $request->getParams()->get('cache.key_filter')) {
            $url = $request->getUrl(true);
            foreach (explode(',', $filter) as $remove) {
                $url->getQuery()->remove(trim($remove));
            }
        } else {
            $url = $request->getUrl();
        }

        return $this->keyPrefix . md5($request->getMethod() . ' ' . $url);
    }

    /**
     * Create a cache key for a response's body
     *
     * @param string              $url  URL of the entry
     * @param EntityBodyInterface $body Response body
     *
     * @return string
     */
    protected function getBodyKey($url, EntityBodyInterface $body)
    {
        return $this->keyPrefix . md5($url) . $body->getContentMd5();
    }

    /**
     * Determines whether two Request HTTP header sets are non-varying
     *
     * @param string $vary Response vary header
     * @param array  $r1   HTTP header array
     * @param array  $r2   HTTP header array
     *
     * @return bool
     */
    private function requestsMatch($vary, $r1, $r2)
    {
        if ($vary) {
            foreach (explode(',', $vary) as $header) {
                $key = trim(strtolower($header));
                $v1 = isset($r1[$key]) ? $r1[$key] : null;
                $v2 = isset($r2[$key]) ? $r2[$key] : null;
                if ($v1 !== $v2) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Creates an array of cacheable and normalized message headers
     *
     * @param MessageInterface $message
     *
     * @return array
     */
    private function persistHeaders(MessageInterface $message)
    {
        // Headers are excluded from the caching (see RFC 2616:13.5.1)
        static $noCache = array(
            'age' => true,
            'connection' => true,
            'keep-alive' => true,
            'proxy-authenticate' => true,
            'proxy-authorization' => true,
            'te' => true,
            'trailers' => true,
            'transfer-encoding' => true,
            'upgrade' => true,
            'set-cookie' => true,
            'set-cookie2' => true
        );

        // Clone the response to not destroy any necessary headers when caching
        $headers = $message->getHeaders()->getAll();
        $headers = array_diff_key($headers, $noCache);
        // Cast the headers to a string
        $headers = array_map(function ($h) { return (string) $h; }, $headers);

        return $headers;
    }
}
