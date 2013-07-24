<?php

namespace Guzzle\Parser\Cookie;

/**
 * Cookie parser interface
 */
interface CookieParserInterface
{
    /**
     * Parse a cookie string as set in a Set-Cookie HTTP header and return an associative array of data.
     *
     * @param string $cookie Cookie header value to parse
     * @param string $host   Host of an associated request
     * @param string $path   Path of an associated request
     * @param bool   $decode Set to TRUE to urldecode cookie values
     *
     * @return array|bool Returns FALSE on failure or returns an array of arrays, with each of the sub arrays including:
     *     - domain  (string) - Domain of the cookie
     *     - path    (string) - Path of the cookie
     *     - cookies (array)  - Associative array of cookie names and values
     *     - max_age (int)    - Lifetime of the cookie in seconds
     *     - version (int)    - Version of the cookie specification. RFC 2965 is 1
     *     - secure  (bool)   - Whether or not this is a secure cookie
     *     - discard (bool)   - Whether or not this is a discardable cookie
     *     - custom (string)  - Custom cookie data array
     *     - comment (string) - How the cookie is intended to be used
     *     - comment_url (str)- URL that contains info on how it will be used
     *     - port (array|str) - Array of ports or null
     *     - http_only (bool) - HTTP only cookie
     */
    public function parseCookie($cookie, $host = null, $path = null, $decode = false);
}
