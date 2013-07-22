<?php

namespace Guzzle\Plugin\Cookie\CookieJar;

use Guzzle\Plugin\Cookie\Cookie;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;

/**
 * Interface for persisting cookies
 */
interface CookieJarInterface extends \Countable, \IteratorAggregate
{
    /**
     * Remove cookies currently held in the Cookie cookieJar.
     *
     * Invoking this method without arguments will empty the whole Cookie cookieJar.  If given a $domain argument only
     * cookies belonging to that domain will be removed. If given a $domain and $path argument, cookies belonging to
     * the specified path within that domain are removed. If given all three arguments, then the cookie with the
     * specified name, path and domain is removed.
     *
     * @param string $domain Set to clear only cookies matching a domain
     * @param string $path   Set to clear only cookies matching a domain and path
     * @param string $name   Set to clear only cookies matching a domain, path, and name
     *
     * @return CookieJarInterface
     */
    public function remove($domain = null, $path = null, $name = null);

    /**
     * Discard all temporary cookies.
     *
     * Scans for all cookies in the cookieJar with either no expire field or a true discard flag. To be called when the
     * user agent shuts down according to RFC 2965.
     *
     * @return CookieJarInterface
     */
    public function removeTemporary();

    /**
     * Delete any expired cookies
     *
     * @return CookieJarInterface
     */
    public function removeExpired();

    /**
     * Add a cookie to the cookie cookieJar
     *
     * @param Cookie $cookie Cookie to add
     *
     * @return bool Returns true on success or false on failure
     */
    public function add(Cookie $cookie);

    /**
     * Add cookies from a {@see Guzzle\Http\Message\Response} object
     *
     * @param Response         $response Response object
     * @param RequestInterface $request  Request that received the response
     */
    public function addCookiesFromResponse(Response $response, RequestInterface $request = null);

    /**
     * Get cookies matching a request object
     *
     * @param RequestInterface $request Request object to match
     *
     * @return array
     */
    public function getMatchingCookies(RequestInterface $request);

    /**
     * Get all of the matching cookies
     *
     * @param string $domain          Domain of the cookie
     * @param string $path            Path of the cookie
     * @param string $name            Name of the cookie
     * @param bool   $skipDiscardable Set to TRUE to skip cookies with the Discard attribute.
     * @param bool   $skipExpired     Set to FALSE to include expired
     *
     * @return array Returns an array of Cookie objects
     */
    public function all($domain = null, $path = null, $name = null, $skipDiscardable = false, $skipExpired = true);
}
