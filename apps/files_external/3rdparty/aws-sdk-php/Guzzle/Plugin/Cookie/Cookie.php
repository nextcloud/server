<?php

namespace Guzzle\Plugin\Cookie;

use Guzzle\Common\ToArrayInterface;

/**
 * Set-Cookie object
 */
class Cookie implements ToArrayInterface
{
    /** @var array Cookie data */
    protected $data;

    /**
     * @var string ASCII codes not valid for for use in a cookie name
     *
     * Cookie names are defined as 'token', according to RFC 2616, Section 2.2
     * A valid token may contain any CHAR except CTLs (ASCII 0 - 31 or 127)
     * or any of the following separators
     */
    protected static $invalidCharString;

    /**
     * Gets an array of invalid cookie characters
     *
     * @return array
     */
    protected static function getInvalidCharacters()
    {
        if (!self::$invalidCharString) {
            self::$invalidCharString = implode('', array_map('chr', array_merge(
                range(0, 32),
                array(34, 40, 41, 44, 47),
                array(58, 59, 60, 61, 62, 63, 64, 91, 92, 93, 123, 125, 127)
            )));
        }

        return self::$invalidCharString;
    }

    /**
     * @param array $data Array of cookie data provided by a Cookie parser
     */
    public function __construct(array $data = array())
    {
        static $defaults = array(
            'name'        => '',
            'value'       => '',
            'domain'      => '',
            'path'        => '/',
            'expires'     => null,
            'max_age'     => 0,
            'comment'     => null,
            'comment_url' => null,
            'port'        => array(),
            'version'     => null,
            'secure'      => false,
            'discard'     => false,
            'http_only'   => false
        );

        $this->data = array_merge($defaults, $data);
        // Extract the expires value and turn it into a UNIX timestamp if needed
        if (!$this->getExpires() && $this->getMaxAge()) {
            // Calculate the expires date
            $this->setExpires(time() + (int) $this->getMaxAge());
        } elseif ($this->getExpires() && !is_numeric($this->getExpires())) {
            $this->setExpires(strtotime($this->getExpires()));
        }
    }

    /**
     * Get the cookie as an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Get the cookie name
     *
     * @return string
     */
    public function getName()
    {
        return $this->data['name'];
    }

    /**
     * Set the cookie name
     *
     * @param string $name Cookie name
     *
     * @return Cookie
     */
    public function setName($name)
    {
        return $this->setData('name', $name);
    }

    /**
     * Get the cookie value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->data['value'];
    }

    /**
     * Set the cookie value
     *
     * @param string $value Cookie value
     *
     * @return Cookie
     */
    public function setValue($value)
    {
        return $this->setData('value', $value);
    }

    /**
     * Get the domain
     *
     * @return string|null
     */
    public function getDomain()
    {
        return $this->data['domain'];
    }

    /**
     * Set the domain of the cookie
     *
     * @param string $domain
     *
     * @return Cookie
     */
    public function setDomain($domain)
    {
        return $this->setData('domain', $domain);
    }

    /**
     * Get the path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->data['path'];
    }

    /**
     * Set the path of the cookie
     *
     * @param string $path Path of the cookie
     *
     * @return Cookie
     */
    public function setPath($path)
    {
        return $this->setData('path', $path);
    }

    /**
     * Maximum lifetime of the cookie in seconds
     *
     * @return int|null
     */
    public function getMaxAge()
    {
        return $this->data['max_age'];
    }

    /**
     * Set the max-age of the cookie
     *
     * @param int $maxAge Max age of the cookie in seconds
     *
     * @return Cookie
     */
    public function setMaxAge($maxAge)
    {
        return $this->setData('max_age', $maxAge);
    }

    /**
     * The UNIX timestamp when the cookie expires
     *
     * @return mixed
     */
    public function getExpires()
    {
        return $this->data['expires'];
    }

    /**
     * Set the unix timestamp for which the cookie will expire
     *
     * @param int $timestamp Unix timestamp
     *
     * @return Cookie
     */
    public function setExpires($timestamp)
    {
        return $this->setData('expires', $timestamp);
    }

    /**
     * Version of the cookie specification. RFC 2965 is 1
     *
     * @return mixed
     */
    public function getVersion()
    {
        return $this->data['version'];
    }

    /**
     * Set the cookie version
     *
     * @param string|int $version Version to set
     *
     * @return Cookie
     */
    public function setVersion($version)
    {
        return $this->setData('version', $version);
    }

    /**
     * Get whether or not this is a secure cookie
     *
     * @return null|bool
     */
    public function getSecure()
    {
        return $this->data['secure'];
    }

    /**
     * Set whether or not the cookie is secure
     *
     * @param bool $secure Set to true or false if secure
     *
     * @return Cookie
     */
    public function setSecure($secure)
    {
        return $this->setData('secure', (bool) $secure);
    }

    /**
     * Get whether or not this is a session cookie
     *
     * @return null|bool
     */
    public function getDiscard()
    {
        return $this->data['discard'];
    }

    /**
     * Set whether or not this is a session cookie
     *
     * @param bool $discard Set to true or false if this is a session cookie
     *
     * @return Cookie
     */
    public function setDiscard($discard)
    {
        return $this->setData('discard', $discard);
    }

    /**
     * Get the comment
     *
     * @return string|null
     */
    public function getComment()
    {
        return $this->data['comment'];
    }

    /**
     * Set the comment of the cookie
     *
     * @param string $comment Cookie comment
     *
     * @return Cookie
     */
    public function setComment($comment)
    {
        return $this->setData('comment', $comment);
    }

    /**
     * Get the comment URL of the cookie
     *
     * @return string|null
     */
    public function getCommentUrl()
    {
        return $this->data['comment_url'];
    }

    /**
     * Set the comment URL of the cookie
     *
     * @param string $commentUrl Cookie comment URL for more information
     *
     * @return Cookie
     */
    public function setCommentUrl($commentUrl)
    {
        return $this->setData('comment_url', $commentUrl);
    }

    /**
     * Get an array of acceptable ports this cookie can be used with
     *
     * @return array
     */
    public function getPorts()
    {
        return $this->data['port'];
    }

    /**
     * Set a list of acceptable ports this cookie can be used with
     *
     * @param array $ports Array of acceptable ports
     *
     * @return Cookie
     */
    public function setPorts(array $ports)
    {
        return $this->setData('port', $ports);
    }

    /**
     * Get whether or not this is an HTTP only cookie
     *
     * @return bool
     */
    public function getHttpOnly()
    {
        return $this->data['http_only'];
    }

    /**
     * Set whether or not this is an HTTP only cookie
     *
     * @param bool $httpOnly Set to true or false if this is HTTP only
     *
     * @return Cookie
     */
    public function setHttpOnly($httpOnly)
    {
        return $this->setData('http_only', $httpOnly);
    }

    /**
     * Get an array of extra cookie data
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->data['data'];
    }

    /**
     * Get a specific data point from the extra cookie data
     *
     * @param string $name Name of the data point to retrieve
     *
     * @return null|string
     */
    public function getAttribute($name)
    {
        return array_key_exists($name, $this->data['data']) ? $this->data['data'][$name] : null;
    }

    /**
     * Set a cookie data attribute
     *
     * @param string $name  Name of the attribute to set
     * @param string $value Value to set
     *
     * @return Cookie
     */
    public function setAttribute($name, $value)
    {
        $this->data['data'][$name] = $value;

        return $this;
    }

    /**
     * Check if the cookie matches a path value
     *
     * @param string $path Path to check against
     *
     * @return bool
     */
    public function matchesPath($path)
    {
        // RFC6265 http://tools.ietf.org/search/rfc6265#section-5.1.4
        // A request-path path-matches a given cookie-path if at least one of
        // the following conditions holds:

        // o  The cookie-path and the request-path are identical.
        if ($path == $this->getPath()) {
            return true;
        }

        $pos = stripos($path, $this->getPath());
        if ($pos === 0) {
            // o  The cookie-path is a prefix of the request-path, and the last
            // character of the cookie-path is %x2F ("/").
            if (substr($this->getPath(), -1, 1) === "/") {
                return true;
            }

            // o  The cookie-path is a prefix of the request-path, and the first
            // character of the request-path that is not included in the cookie-
            // path is a %x2F ("/") character.
            if (substr($path, strlen($this->getPath()), 1) === "/") {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the cookie matches a domain value
     *
     * @param string $domain Domain to check against
     *
     * @return bool
     */
    public function matchesDomain($domain)
    {
        // Remove the leading '.' as per spec in RFC 6265: http://tools.ietf.org/html/rfc6265#section-5.2.3
        $cookieDomain = ltrim($this->getDomain(), '.');

        // Domain not set or exact match.
        if (!$cookieDomain || !strcasecmp($domain, $cookieDomain)) {
            return true;
        }

        // Matching the subdomain according to RFC 6265: http://tools.ietf.org/html/rfc6265#section-5.1.3
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return false;
        }

        return (bool) preg_match('/\.' . preg_quote($cookieDomain, '/') . '$/i', $domain);
    }

    /**
     * Check if the cookie is compatible with a specific port
     *
     * @param int $port Port to check
     *
     * @return bool
     */
    public function matchesPort($port)
    {
        return count($this->getPorts()) == 0 || in_array($port, $this->getPorts());
    }

    /**
     * Check if the cookie is expired
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->getExpires() && time() > $this->getExpires();
    }

    /**
     * Check if the cookie is valid according to RFC 6265
     *
     * @return bool|string Returns true if valid or an error message if invalid
     */
    public function validate()
    {
        // Names must not be empty, but can be 0
        $name = $this->getName();
        if (empty($name) && !is_numeric($name)) {
            return 'The cookie name must not be empty';
        }

        // Check if any of the invalid characters are present in the cookie name
        if (strpbrk($name, self::getInvalidCharacters()) !== false) {
            return 'The cookie name must not contain invalid characters: ' . $name;
        }

        // Value must not be empty, but can be 0
        $value = $this->getValue();
        if (empty($value) && !is_numeric($value)) {
            return 'The cookie value must not be empty';
        }

        // Domains must not be empty, but can be 0
        // A "0" is not a valid internet domain, but may be used as server name in a private network
        $domain = $this->getDomain();
        if (empty($domain) && !is_numeric($domain)) {
            return 'The cookie domain must not be empty';
        }

        return true;
    }

    /**
     * Set a value and return the cookie object
     *
     * @param string $key   Key to set
     * @param string $value Value to set
     *
     * @return Cookie
     */
    private function setData($key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }
}
