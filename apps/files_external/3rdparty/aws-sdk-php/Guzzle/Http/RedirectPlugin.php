<?php

namespace Guzzle\Http;

use Guzzle\Common\Event;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Url;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\RequestFactory;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\Exception\TooManyRedirectsException;
use Guzzle\Http\Exception\CouldNotRewindStreamException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Plugin to implement HTTP redirects. Can redirect like a web browser or using strict RFC 2616 compliance
 */
class RedirectPlugin implements EventSubscriberInterface
{
    const REDIRECT_COUNT = 'redirect.count';
    const MAX_REDIRECTS = 'redirect.max';
    const STRICT_REDIRECTS = 'redirect.strict';
    const PARENT_REQUEST = 'redirect.parent_request';
    const DISABLE = 'redirect.disable';

    /**
     * @var int Default number of redirects allowed when no setting is supplied by a request
     */
    protected $defaultMaxRedirects = 5;

    public static function getSubscribedEvents()
    {
        return array(
            'request.sent'        => array('onRequestSent', 100),
            'request.clone'       => 'cleanupRequest',
            'request.before_send' => 'cleanupRequest'
        );
    }

    /**
     * Clean up the parameters of a request when it is cloned
     *
     * @param Event $event Event emitted
     */
    public function cleanupRequest(Event $event)
    {
        $params = $event['request']->getParams();
        unset($params[self::REDIRECT_COUNT]);
        unset($params[self::PARENT_REQUEST]);
    }

    /**
     * Called when a request receives a redirect response
     *
     * @param Event $event Event emitted
     */
    public function onRequestSent(Event $event)
    {
        $response = $event['response'];
        $request = $event['request'];

        // Only act on redirect requests with Location headers
        if (!$response || $request->getParams()->get(self::DISABLE)) {
            return;
        }

        // Trace the original request based on parameter history
        $original = $this->getOriginalRequest($request);

        // Terminating condition to set the effective repsonse on the original request
        if (!$response->isRedirect() || !$response->hasHeader('Location')) {
            if ($request !== $original) {
                // This is a terminating redirect response, so set it on the original request
                $response->getParams()->set(self::REDIRECT_COUNT, $original->getParams()->get(self::REDIRECT_COUNT));
                $original->setResponse($response);
                $response->setEffectiveUrl($request->getUrl());
            }
            return;
        }

        $this->sendRedirectRequest($original, $request, $response);
    }

    /**
     * Get the original request that initiated a series of redirects
     *
     * @param RequestInterface $request Request to get the original request from
     *
     * @return RequestInterface
     */
    protected function getOriginalRequest(RequestInterface $request)
    {
        $original = $request;
        // The number of redirects is held on the original request, so determine which request that is
        while ($parent = $original->getParams()->get(self::PARENT_REQUEST)) {
            $original = $parent;
        }

        return $original;
    }

    /**
     * Create a redirect request for a specific request object
     *
     * Takes into account strict RFC compliant redirection (e.g. redirect POST with POST) vs doing what most clients do
     * (e.g. redirect POST with GET).
     *
     * @param RequestInterface $request    Request being redirected
     * @param RequestInterface $original   Original request
     * @param int              $statusCode Status code of the redirect
     * @param string           $location   Location header of the redirect
     *
     * @return RequestInterface Returns a new redirect request
     * @throws CouldNotRewindStreamException If the body needs to be rewound but cannot
     */
    protected function createRedirectRequest(
        RequestInterface $request,
        $statusCode,
        $location,
        RequestInterface $original
    ) {
        $redirectRequest = null;
        $strict = $original->getParams()->get(self::STRICT_REDIRECTS);

        // Use a GET request if this is an entity enclosing request and we are not forcing RFC compliance, but rather
        // emulating what all browsers would do
        if ($request instanceof EntityEnclosingRequestInterface && !$strict && $statusCode <= 302) {
            $redirectRequest = RequestFactory::getInstance()->cloneRequestWithMethod($request, 'GET');
        } else {
            $redirectRequest = clone $request;
        }

        $redirectRequest->setIsRedirect(true);
        // Always use the same response body when redirecting
        $redirectRequest->setResponseBody($request->getResponseBody());

        $location = Url::factory($location);
        // If the location is not absolute, then combine it with the original URL
        if (!$location->isAbsolute()) {
            $originalUrl = $redirectRequest->getUrl(true);
            // Remove query string parameters and just take what is present on the redirect Location header
            $originalUrl->getQuery()->clear();
            $location = $originalUrl->combine((string) $location);
        }

        $redirectRequest->setUrl($location);

        // Add the parent request to the request before it sends (make sure it's before the onRequestClone event too)
        $redirectRequest->getEventDispatcher()->addListener(
            'request.before_send',
            $func = function ($e) use (&$func, $request, $redirectRequest) {
                $redirectRequest->getEventDispatcher()->removeListener('request.before_send', $func);
                $e['request']->getParams()->set(RedirectPlugin::PARENT_REQUEST, $request);
            }
        );

        // Rewind the entity body of the request if needed
        if ($redirectRequest instanceof EntityEnclosingRequestInterface && $redirectRequest->getBody()) {
            $body = $redirectRequest->getBody();
            // Only rewind the body if some of it has been read already, and throw an exception if the rewind fails
            if ($body->ftell() && !$body->rewind()) {
                throw new CouldNotRewindStreamException(
                    'Unable to rewind the non-seekable entity body of the request after redirecting. cURL probably '
                    . 'sent part of body before the redirect occurred. Try adding acustom rewind function using on the '
                    . 'entity body of the request using setRewindFunction().'
                );
            }
        }

        return $redirectRequest;
    }

    /**
     * Prepare the request for redirection and enforce the maximum number of allowed redirects per client
     *
     * @param RequestInterface $original  Origina request
     * @param RequestInterface $request   Request to prepare and validate
     * @param Response         $response  The current response
     *
     * @return RequestInterface
     */
    protected function prepareRedirection(RequestInterface $original, RequestInterface $request, Response $response)
    {
        $params = $original->getParams();
        // This is a new redirect, so increment the redirect counter
        $current = $params[self::REDIRECT_COUNT] + 1;
        $params[self::REDIRECT_COUNT] = $current;
        // Use a provided maximum value or default to a max redirect count of 5
        $max = isset($params[self::MAX_REDIRECTS]) ? $params[self::MAX_REDIRECTS] : $this->defaultMaxRedirects;

        // Throw an exception if the redirect count is exceeded
        if ($current > $max) {
            $this->throwTooManyRedirectsException($original, $max);
            return false;
        } else {
            // Create a redirect request based on the redirect rules set on the request
            return $this->createRedirectRequest(
                $request,
                $response->getStatusCode(),
                trim($response->getLocation()),
                $original
            );
        }
    }

    /**
     * Send a redirect request and handle any errors
     *
     * @param RequestInterface $original The originating request
     * @param RequestInterface $request  The current request being redirected
     * @param Response         $response The response of the current request
     *
     * @throws BadResponseException|\Exception
     */
    protected function sendRedirectRequest(RequestInterface $original, RequestInterface $request, Response $response)
    {
        // Validate and create a redirect request based on the original request and current response
        if ($redirectRequest = $this->prepareRedirection($original, $request, $response)) {
            try {
                $redirectRequest->send();
            } catch (BadResponseException $e) {
                $e->getResponse();
                if (!$e->getResponse()) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Throw a too many redirects exception for a request
     *
     * @param RequestInterface $original Request
     * @param int              $max      Max allowed redirects
     *
     * @throws TooManyRedirectsException when too many redirects have been issued
     */
    protected function throwTooManyRedirectsException(RequestInterface $original, $max)
    {
        $original->getEventDispatcher()->addListener(
            'request.complete',
            $func = function ($e) use (&$func, $original, $max) {
                $original->getEventDispatcher()->removeListener('request.complete', $func);
                $str = "{$max} redirects were issued for this request:\n" . $e['request']->getRawHeaders();
                throw new TooManyRedirectsException($str);
            }
        );
    }
}
