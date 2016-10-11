<?php

namespace Guzzle\Plugin\Cookie\CookieJar;

use Guzzle\Plugin\Cookie\Cookie;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Guzzle\Parser\ParserRegistry;
use Guzzle\Plugin\Cookie\Exception\InvalidCookieException;

/**
 * Cookie cookieJar that stores cookies an an array
 */
class ArrayCookieJar implements CookieJarInterface, \Serializable
{
    /** @var array Loaded cookie data */
    protected $cookies = array();

    /** @var bool Whether or not strict mode is enabled. When enabled, exceptions will be thrown for invalid cookies */
    protected $strictMode;

    /**
     * @param bool $strictMode Set to true to throw exceptions when invalid cookies are added to the cookie jar
     */
    public function __construct($strictMode = false)
    {
        $this->strictMode = $strictMode;
    }

    /**
     * Enable or disable strict mode on the cookie jar
     *
     * @param bool $strictMode Set to true to throw exceptions when invalid cookies are added. False to ignore them.
     *
     * @return self
     */
    public function setStrictMode($strictMode)
    {
        $this->strictMode = $strictMode;
    }

    public function remove($domain = null, $path = null, $name = null)
    {
        $cookies = $this->all($domain, $path, $name, false, false);
        $this->cookies = array_filter($this->cookies, function (Cookie $cookie) use ($cookies) {
            return !in_array($cookie, $cookies, true);
        });

        return $this;
    }

    public function removeTemporary()
    {
        $this->cookies = array_filter($this->cookies, function (Cookie $cookie) {
            return !$cookie->getDiscard() && $cookie->getExpires();
        });

        return $this;
    }

    public function removeExpired()
    {
        $currentTime = time();
        $this->cookies = array_filter($this->cookies, function (Cookie $cookie) use ($currentTime) {
            return !$cookie->getExpires() || $currentTime < $cookie->getExpires();
        });

        return $this;
    }

    public function all($domain = null, $path = null, $name = null, $skipDiscardable = false, $skipExpired = true)
    {
        return array_values(array_filter($this->cookies, function (Cookie $cookie) use (
            $domain,
            $path,
            $name,
            $skipDiscardable,
            $skipExpired
        ) {
            return false === (($name && $cookie->getName() != $name) ||
                ($skipExpired && $cookie->isExpired()) ||
                ($skipDiscardable && ($cookie->getDiscard() || !$cookie->getExpires())) ||
                ($path && !$cookie->matchesPath($path)) ||
                ($domain && !$cookie->matchesDomain($domain)));
        }));
    }

    public function add(Cookie $cookie)
    {
        // Only allow cookies with set and valid domain, name, value
        $result = $cookie->validate();
        if ($result !== true) {
            if ($this->strictMode) {
                throw new InvalidCookieException($result);
            } else {
                $this->removeCookieIfEmpty($cookie);
                return false;
            }
        }

        // Resolve conflicts with previously set cookies
        foreach ($this->cookies as $i => $c) {

            // Two cookies are identical, when their path, domain, port and name are identical
            if ($c->getPath() != $cookie->getPath() ||
                $c->getDomain() != $cookie->getDomain() ||
                $c->getPorts() != $cookie->getPorts() ||
                $c->getName() != $cookie->getName()
            ) {
                continue;
            }

            // The previously set cookie is a discard cookie and this one is not so allow the new cookie to be set
            if (!$cookie->getDiscard() && $c->getDiscard()) {
                unset($this->cookies[$i]);
                continue;
            }

            // If the new cookie's expiration is further into the future, then replace the old cookie
            if ($cookie->getExpires() > $c->getExpires()) {
                unset($this->cookies[$i]);
                continue;
            }

            // If the value has changed, we better change it
            if ($cookie->getValue() !== $c->getValue()) {
                unset($this->cookies[$i]);
                continue;
            }

            // The cookie exists, so no need to continue
            return false;
        }

        $this->cookies[] = $cookie;

        return true;
    }

    /**
     * Serializes the cookie cookieJar
     *
     * @return string
     */
    public function serialize()
    {
        // Only serialize long term cookies and unexpired cookies
        return json_encode(array_map(function (Cookie $cookie) {
            return $cookie->toArray();
        }, $this->all(null, null, null, true, true)));
    }

    /**
     * Unserializes the cookie cookieJar
     */
    public function unserialize($data)
    {
        $data = json_decode($data, true);
        if (empty($data)) {
            $this->cookies = array();
        } else {
            $this->cookies = array_map(function (array $cookie) {
                return new Cookie($cookie);
            }, $data);
        }
    }

    /**
     * Returns the total number of stored cookies
     *
     * @return int
     */
    public function count()
    {
        return count($this->cookies);
    }

    /**
     * Returns an iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->cookies);
    }

    public function addCookiesFromResponse(Response $response, RequestInterface $request = null)
    {
        if ($cookieHeader = $response->getHeader('Set-Cookie')) {
            $parser = ParserRegistry::getInstance()->getParser('cookie');
            foreach ($cookieHeader as $cookie) {
                if ($parsed = $request
                    ? $parser->parseCookie($cookie, $request->getHost(), $request->getPath())
                    : $parser->parseCookie($cookie)
                ) {
                    // Break up cookie v2 into multiple cookies
                    foreach ($parsed['cookies'] as $key => $value) {
                        $row = $parsed;
                        $row['name'] = $key;
                        $row['value'] = $value;
                        unset($row['cookies']);
                        $this->add(new Cookie($row));
                    }
                }
            }
        }
    }

    public function getMatchingCookies(RequestInterface $request)
    {
        // Find cookies that match this request
        $cookies = $this->all($request->getHost(), $request->getPath());
        // Remove ineligible cookies
        foreach ($cookies as $index => $cookie) {
            if (!$cookie->matchesPort($request->getPort()) || ($cookie->getSecure() && $request->getScheme() != 'https')) {
                unset($cookies[$index]);
            }
        };

        return $cookies;
    }

    /**
     * If a cookie already exists and the server asks to set it again with a null value, the
     * cookie must be deleted.
     *
     * @param \Guzzle\Plugin\Cookie\Cookie $cookie
     */
    private function removeCookieIfEmpty(Cookie $cookie)
    {
        $cookieValue = $cookie->getValue();
        if ($cookieValue === null || $cookieValue === '') {
            $this->remove($cookie->getDomain(), $cookie->getPath(), $cookie->getName());
        }
    }
}
