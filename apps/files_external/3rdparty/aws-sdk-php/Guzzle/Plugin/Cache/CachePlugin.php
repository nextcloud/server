<?php

namespace Guzzle\Plugin\Cache;

use Guzzle\Cache\CacheAdapterFactory;
use Guzzle\Cache\CacheAdapterInterface;
use Guzzle\Common\Event;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Version;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Http\Exception\CurlException;
use Doctrine\Common\Cache\ArrayCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Plugin to enable the caching of GET and HEAD requests.  Caching can be done on all requests passing through this
 * plugin or only after retrieving resources with cacheable response headers.
 *
 * This is a simple implementation of RFC 2616 and should be considered a private transparent proxy cache, meaning
 * authorization and private data can be cached.
 *
 * It also implements RFC 5861's `stale-if-error` Cache-Control extension, allowing stale cache responses to be used
 * when an error is encountered (such as a `500 Internal Server Error` or DNS failure).
 */
class CachePlugin implements EventSubscriberInterface
{
    /** @var RevalidationInterface Cache revalidation strategy */
    protected $revalidation;

    /** @var CanCacheStrategyInterface Object used to determine if a request can be cached */
    protected $canCache;

    /** @var CacheStorageInterface $cache Object used to cache responses */
    protected $storage;

    /** @var bool */
    protected $autoPurge;

    /**
     * @param array|CacheAdapterInterface|CacheStorageInterface $options Array of options for the cache plugin,
     *     cache adapter, or cache storage object.
     *     - CacheStorageInterface storage:      Adapter used to cache responses
     *     - RevalidationInterface revalidation: Cache revalidation strategy
     *     - CanCacheInterface     can_cache:    Object used to determine if a request can be cached
     *     - bool                  auto_purge    Set to true to automatically PURGE resources when non-idempotent
     *                                           requests are sent to a resource. Defaults to false.
     * @throws InvalidArgumentException if no cache is provided and Doctrine cache is not installed
     */
    public function __construct($options = null)
    {
        if (!is_array($options)) {
            if ($options instanceof CacheAdapterInterface) {
                $options = array('storage' => new DefaultCacheStorage($options));
            } elseif ($options instanceof CacheStorageInterface) {
                $options = array('storage' => $options);
            } elseif ($options) {
                $options = array('storage' => new DefaultCacheStorage(CacheAdapterFactory::fromCache($options)));
            } elseif (!class_exists('Doctrine\Common\Cache\ArrayCache')) {
                // @codeCoverageIgnoreStart
                throw new InvalidArgumentException('No cache was provided and Doctrine is not installed');
                // @codeCoverageIgnoreEnd
            }
        }

        $this->autoPurge = isset($options['auto_purge']) ? $options['auto_purge'] : false;

        // Add a cache storage if a cache adapter was provided
        $this->storage = isset($options['storage'])
            ? $options['storage']
            : new DefaultCacheStorage(new DoctrineCacheAdapter(new ArrayCache()));

        if (!isset($options['can_cache'])) {
            $this->canCache = new DefaultCanCacheStrategy();
        } else {
            $this->canCache = is_callable($options['can_cache'])
                ? new CallbackCanCacheStrategy($options['can_cache'])
                : $options['can_cache'];
        }

        // Use the provided revalidation strategy or the default
        $this->revalidation = isset($options['revalidation'])
            ? $options['revalidation']
            : new DefaultRevalidation($this->storage, $this->canCache);
    }

    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send' => array('onRequestBeforeSend', -255),
            'request.sent'        => array('onRequestSent', 255),
            'request.error'       => array('onRequestError', 0),
            'request.exception'   => array('onRequestException', 0),
        );
    }

    /**
     * Check if a response in cache will satisfy the request before sending
     *
     * @param Event $event
     */
    public function onRequestBeforeSend(Event $event)
    {
        $request = $event['request'];
        $request->addHeader('Via', sprintf('%s GuzzleCache/%s', $request->getProtocolVersion(), Version::VERSION));

        if (!$this->canCache->canCacheRequest($request)) {
            switch ($request->getMethod()) {
                case 'PURGE':
                    $this->purge($request);
                    $request->setResponse(new Response(200, array(), 'purged'));
                    break;
                case 'PUT':
                case 'POST':
                case 'DELETE':
                case 'PATCH':
                    if ($this->autoPurge) {
                        $this->purge($request);
                    }
            }
            return;
        }

        if ($response = $this->storage->fetch($request)) {
            $params = $request->getParams();
            $params['cache.lookup'] = true;
            $response->setHeader(
                'Age',
                time() - strtotime($response->getDate() ? : $response->getLastModified() ?: 'now')
            );
            // Validate that the response satisfies the request
            if ($this->canResponseSatisfyRequest($request, $response)) {
                if (!isset($params['cache.hit'])) {
                    $params['cache.hit'] = true;
                }
                $request->setResponse($response);
            }
        }
    }

    /**
     * If possible, store a response in cache after sending
     *
     * @param Event $event
     */
    public function onRequestSent(Event $event)
    {
        $request = $event['request'];
        $response = $event['response'];

        if ($request->getParams()->get('cache.hit') === null &&
            $this->canCache->canCacheRequest($request) &&
            $this->canCache->canCacheResponse($response)
        ) {
            $this->storage->cache($request, $response);
        }

        $this->addResponseHeaders($request, $response);
    }

    /**
     * If possible, return a cache response on an error
     *
     * @param Event $event
     */
    public function onRequestError(Event $event)
    {
        $request = $event['request'];

        if (!$this->canCache->canCacheRequest($request)) {
            return;
        }

        if ($response = $this->storage->fetch($request)) {
            $response->setHeader(
                'Age',
                time() - strtotime($response->getLastModified() ? : $response->getDate() ?: 'now')
            );

            if ($this->canResponseSatisfyFailedRequest($request, $response)) {
                $request->getParams()->set('cache.hit', 'error');
                $this->addResponseHeaders($request, $response);
                $event['response'] = $response;
                $event->stopPropagation();
            }
        }
    }

    /**
     * If possible, set a cache response on a cURL exception
     *
     * @param Event $event
     *
     * @return null
     */
    public function onRequestException(Event $event)
    {
        if (!$event['exception'] instanceof CurlException) {
            return;
        }

        $request = $event['request'];
        if (!$this->canCache->canCacheRequest($request)) {
            return;
        }

        if ($response = $this->storage->fetch($request)) {
            $response->setHeader('Age', time() - strtotime($response->getDate() ? : 'now'));
            if (!$this->canResponseSatisfyFailedRequest($request, $response)) {
                return;
            }
            $request->getParams()->set('cache.hit', 'error');
            $request->setResponse($response);
            $this->addResponseHeaders($request, $response);
            $event->stopPropagation();
        }
    }

    /**
     * Check if a cache response satisfies a request's caching constraints
     *
     * @param RequestInterface $request  Request to validate
     * @param Response         $response Response to validate
     *
     * @return bool
     */
    public function canResponseSatisfyRequest(RequestInterface $request, Response $response)
    {
        $responseAge = $response->calculateAge();
        $reqc = $request->getHeader('Cache-Control');
        $resc = $response->getHeader('Cache-Control');

        // Check the request's max-age header against the age of the response
        if ($reqc && $reqc->hasDirective('max-age') &&
            $responseAge > $reqc->getDirective('max-age')) {
            return false;
        }

        // Check the response's max-age header
        if ($response->isFresh() === false) {
            $maxStale = $reqc ? $reqc->getDirective('max-stale') : null;
            if (null !== $maxStale) {
                if ($maxStale !== true && $response->getFreshness() < (-1 * $maxStale)) {
                    return false;
                }
            } elseif ($resc && $resc->hasDirective('max-age')
                && $responseAge > $resc->getDirective('max-age')
            ) {
                return false;
            }
        }

        if ($this->revalidation->shouldRevalidate($request, $response)) {
            try {
                return $this->revalidation->revalidate($request, $response);
            } catch (CurlException $e) {
                $request->getParams()->set('cache.hit', 'error');
                return $this->canResponseSatisfyFailedRequest($request, $response);
            }
        }

        return true;
    }

    /**
     * Check if a cache response satisfies a failed request's caching constraints
     *
     * @param RequestInterface $request  Request to validate
     * @param Response         $response Response to validate
     *
     * @return bool
     */
    public function canResponseSatisfyFailedRequest(RequestInterface $request, Response $response)
    {
        $reqc = $request->getHeader('Cache-Control');
        $resc = $response->getHeader('Cache-Control');
        $requestStaleIfError = $reqc ? $reqc->getDirective('stale-if-error') : null;
        $responseStaleIfError = $resc ? $resc->getDirective('stale-if-error') : null;

        if (!$requestStaleIfError && !$responseStaleIfError) {
            return false;
        }

        if (is_numeric($requestStaleIfError) && $response->getAge() - $response->getMaxAge() > $requestStaleIfError) {
            return false;
        }

        if (is_numeric($responseStaleIfError) && $response->getAge() - $response->getMaxAge() > $responseStaleIfError) {
            return false;
        }

        return true;
    }

    /**
     * Purge all cache entries for a given URL
     *
     * @param string $url URL to purge
     */
    public function purge($url)
    {
        // BC compatibility with previous version that accepted a Request object
        $url = $url instanceof RequestInterface ? $url->getUrl() : $url;
        $this->storage->purge($url);
    }

    /**
     * Add the plugin's headers to a response
     *
     * @param RequestInterface $request  Request
     * @param Response         $response Response to add headers to
     */
    protected function addResponseHeaders(RequestInterface $request, Response $response)
    {
        $params = $request->getParams();
        $response->setHeader('Via', sprintf('%s GuzzleCache/%s', $request->getProtocolVersion(), Version::VERSION));

        $lookup = ($params['cache.lookup'] === true ? 'HIT' : 'MISS') . ' from GuzzleCache';
        if ($header = $response->getHeader('X-Cache-Lookup')) {
            // Don't add duplicates
            $values = $header->toArray();
            $values[] = $lookup;
            $response->setHeader('X-Cache-Lookup', array_unique($values));
        } else {
            $response->setHeader('X-Cache-Lookup', $lookup);
        }

        if ($params['cache.hit'] === true) {
            $xcache = 'HIT from GuzzleCache';
        } elseif ($params['cache.hit'] == 'error') {
            $xcache = 'HIT_ERROR from GuzzleCache';
        } else {
            $xcache = 'MISS from GuzzleCache';
        }

        if ($header = $response->getHeader('X-Cache')) {
            // Don't add duplicates
            $values = $header->toArray();
            $values[] = $xcache;
            $response->setHeader('X-Cache', array_unique($values));
        } else {
            $response->setHeader('X-Cache', $xcache);
        }

        if ($response->isFresh() === false) {
            $response->addHeader('Warning', sprintf('110 GuzzleCache/%s "Response is stale"', Version::VERSION));
            if ($params['cache.hit'] === 'error') {
                $response->addHeader('Warning', sprintf('111 GuzzleCache/%s "Revalidation failed"', Version::VERSION));
            }
        }
    }
}
